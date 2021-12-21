<?php

namespace W3com\BoomBundle\Form;

use Doctrine\Common\Annotations\AnnotationReader;
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;
use W3com\BoomBundle\Form\Type\SapCheckboxType;
use W3com\BoomBundle\Form\Type\SapDateType;
use W3com\BoomBundle\Form\Type\SapTimeType;

class BoomTypeGuesser implements FormTypeGuesserInterface
{
    /**
     * Returns a field guess for a property name of a class.
     *
     * @param string $class The fully qualified class name
     * @param string $property The name of the property to guess for
     *
     * @return void|null|TypeGuess
     *
     * @throws ReflectionException
     */
    public function guessType($class, $property)
    {
        $annotations = $this->readPhpDocAnnotations($class, $property);

        if (!isset($annotations['type'])) {
            return; // guess nothing if the @var annotation is not available
        }

        // otherwise, base the type on the @var annotation
        switch ($annotations['type']) {
            case 'string':
                // there is a high confidence that the type is text when
                // @var string is used
                return new TypeGuess(
                    TextType::class,
                    [
                        'label' => $annotations['label']
                    ]
                    , Guess::HIGH_CONFIDENCE
                );
            case 'choice':
                $choicesElement = explode('#' ,$annotations['choices']);
                $choices = [];
                if(count($choicesElement) > 0){
                    foreach($choicesElement as $element) {
                        $tmp = explode('|',$element);

                        // Condition to trigger the SapCheckboxType class
                        if ($tmp[0] === 'Oui' && $tmp[1] === 'Y') {
                            $choices[$tmp[0]] = $tmp[1];
                        } elseif ($tmp[0] === 'Non' && $tmp[1] === 'N') {
                            $choices[$tmp[0]] = $tmp[1];
                        }

                        if (count($tmp) == 2) {
                            $choices[$tmp[0]] = $tmp[1];
                        } else {
                            $choices[$element] = $element;
                        }
                    }
                    if (count($choicesElement) === 2 && array_key_exists('Oui', $choices) && array_key_exists('Non', $choices)) {
                        return new TypeGuess(SapCheckboxType::class,
                            [
                                'label' => $annotations['label'],
                            ],
                            Guess::HIGH_CONFIDENCE
                        );
                    }
                }
                return new TypeGuess(ChoiceType::class,
                    [
                        'label' => $annotations['label'],
                        'choices' => $choices,
                    ],
                    Guess::HIGH_CONFIDENCE
                );
            case 'int':
            case 'integer':
                // integers can also be the id of an entity or a checkbox (0 or 1)
                return new TypeGuess(IntegerType::class,
                    [
                        'label' => $annotations['label'],
                    ],
                    Guess::MEDIUM_CONFIDENCE
                );
            case 'date':
                return new TypeGuess(SapDateType::class,
                    [
                        'label' => $annotations['label'],
                    ],
                    Guess::MEDIUM_CONFIDENCE
                );
            case 'time':
                return new TypeGuess(SapTimeType::class,
                    [
                        'label' => $annotations['label'],
                    ],
                    Guess::MEDIUM_CONFIDENCE
                );
            case 'float':
            case 'double':
            case 'real':
                return new TypeGuess(NumberType::class,
                    [
                        'label' => $annotations['label'],
                    ],
                    Guess::MEDIUM_CONFIDENCE
                );
            case 'boolean':
            case 'bool':
                return new TypeGuess(CheckboxType::class,
                    [
                        'label' => $annotations['label,']
                    ],
                    Guess::HIGH_CONFIDENCE
                );
            case 'hidden':
                return new TypeGuess(HiddenType::class,
                    [
                        'label' => $annotations['label'],
                    ],
                    Guess::MEDIUM_CONFIDENCE
                );
            default:
                // there is a very low confidence that this one is correct
                return new TypeGuess(HiddenType::class,
                    [
                        'label' => $annotations['labe,l']
                    ],
                    Guess::LOW_CONFIDENCE
                );
        }
    }

    /**
     * Returns a guess whether a property of a class is required.
     *
     * @param string $class The fully qualified class name
     * @param string $property The name of the property to guess for
     *
     * @return void A guess for the field's required setting
     */
    public function guessRequired($class, $property)
    {
        return null;
    }

    /**
     * Returns a guess about the field's maximum length.
     *
     * @param string $class The fully qualified class name
     * @param string $property The name of the property to guess for
     *
     * @return Guess\ValueGuess|null A guess for the field's maximum length
     */
    public function guessMaxLength($class, $property)
    {
        return null;
    }

    /**
     * Returns a guess about the field's pattern.
     *
     * - When you have a min value, you guess a min length of this min (LOW_CONFIDENCE) , lines below
     * - If this value is a float type, this is wrong so you guess null with MEDIUM_CONFIDENCE to override the previous guess.
     * Example:
     *  You want a float greater than 5, 4.512313 is not valid but length(4.512314) > length(5)
     *
     * @see https://github.com/symfony/symfony/pull/3927
     *
     * @param string $class The fully qualified class name
     * @param string $property The name of the property to guess for
     *
     * @return Guess\ValueGuess|null A guess for the field's required pattern
     */
    public function guessPattern($class, $property)
    {
        return null;
    }

    /**
     * @throws ReflectionException
     */
    protected function readPhpDocAnnotations(string $class, string $property): array
    {
        $reflProperty = new ReflectionProperty($class, $property);
        $reader = new AnnotationReader();
        $ar = [];

        if (
            $annotation = $reader->getPropertyAnnotation(
                $reflProperty,
                'W3com\\BoomBundle\\Annotation\\EntityColumnMeta'
            )
        ) {
            $ar['choices'] = $annotation->choices;
            $ar['label'] = $annotation->description;

            if (null != $annotation->choices) {
                $ar['type'] = 'choice';
            } else {
                $ar['type'] = $annotation->type;
            }
        }

        return $ar;
    }
}