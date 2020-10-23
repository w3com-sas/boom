<?php

namespace W3com\BoomBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class SapCheckboxType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->addModelTransformer(new CallbackTransformer(
                function ($fieldAsBool) {
                    // Transform the string to a bool
                    return $fieldAsBool === 'Y';
                },
                function ($fieldAsString) {
                    // Transform the bool back to a string
                    return $fieldAsString ? 'Y' : 'N';
                }
            ))
        ;
    }

    public function getParent(): string
    {
        return CheckboxType::class;
    }
}