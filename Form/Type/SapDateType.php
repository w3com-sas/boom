<?php

namespace W3com\BoomBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use W3com\BoomBundle\Form\DataTransformer\SapDateTransformer;

class SapDateType extends AbstractType
{
    private $transformer;

    public function __construct(SapDateTransformer $dateTransformer)
    {
        $this->transformer = $dateTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this->transformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'attr' => ['class' => 'datepicker-here', 'data-language' => 'fr'],
        ]);
    }

    public function getParent()
    {
        return TextType::class;
    }

}