<?php

namespace W3com\BoomBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use W3com\BoomBundle\Form\DataTransformer\SapTimeTransformer;

class SapTimeType extends AbstractType
{
    private $transformer;

    public function __construct(SapTimeTransformer $timeTransformer)
    {
        $this->transformer = $timeTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this->transformer);
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'attr' => [
                'class' => 'datepicker-here time-only',
                'data-language' => 'fr',
                'data-timepicker' => 'true',
                'data-time-format' => 'hh:ii',
                'data-only-timepicker' => 'true'
            ],
        ]);
    }

    public function getParent()
    {
        return TextType::class;
    }

}