<?php

namespace W3com\BoomBundle\Form\DataTransformer;

use DateTime;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class SapDateTransformer implements DataTransformerInterface
{

    /**
     * @param mixed $value The value in the original representation
     *
     * @return mixed The value in the transformed representation
     *
     * @throws TransformationFailedException when the transformation fails
     */
    public function transform($value)
    {
        if ($value === null) return $value;
        if (substr($value, 0, 5) === '/Date') {
            $stamp = substr($value, 6, -5);
            return date('d/m/Y', $stamp);
        } elseif (DateTime::createFromFormat('Y-m-d', $value) instanceof \DateTimeInterface) {
            $date = DateTime::createFromFormat('Y-m-d', $value);
            return $date->format('d/m/Y');
        }
        return $value;
    }

    /**
     * Transforms a value from the transformed representation to its original
     * representation.
     *
     * This method is called when {@link Form::submit()} is called to transform the requests tainted data
     * into an acceptable format.
     *
     * The same transformers are called in the reverse order so the responsibility is to
     * return one of the types that would be expected as input of transform().
     *
     * This method must be able to deal with empty values. Usually this will
     * be an empty string, but depending on your implementation other empty
     * values are possible as well (such as NULL). The reasoning behind
     * this is that value transformers must be chainable. If the
     * reverseTransform() method of the first value transformer outputs an
     * empty string, the second value transformer must be able to process that
     * value.
     *
     * By convention, reverseTransform() should return NULL if an empty string
     * is passed.
     *
     * @param mixed $value The value in the transformed representation
     *
     * @return mixed The value in the original representation
     *
     * @throws TransformationFailedException when the transformation fails
     */
    public function reverseTransform($value)
    {
        if (empty($value)) return null;
        if (\DateTime::createFromFormat('d/m/Y', $value) instanceof \DateTimeInterface) {
            return \DateTime::createFromFormat('d/m/Y', $value)->format('Y-m-d');
        }
        return $value;
    }
}