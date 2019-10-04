<?php

namespace W3com\BoomBundle\Generator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Symfony\Component\Form\Exception\TransformationFailedException;
use W3com\BoomBundle\Annotation\EntityColumnMeta;
use W3com\BoomBundle\Annotation\EntityMeta;
use W3com\BoomBundle\HanaEntity\AbstractEntity;
use W3com\BoomBundle\Service\BoomGenerator;
use W3com\BoomBundle\Service\BoomManager;
use W3com\BoomBundle\Generator\Model\Entity;
use W3com\BoomBundle\Generator\Model\Property;

class ClassCreator
{
    private $manager;
    private $generator;

    public function __construct(BoomManager $manager, BoomGenerator $generator)
    {
        $this->manager = $manager;
        $this->generator = $generator;
    }

    public function generateClass(Entity $entity, $type = 'ods', $fields = [])
    {
        $file = $this->getBaseFile();
        $namespace = $this->getNamespace($file);

        $entity = $this->generator->getSLInspector()->addMetaToEntity($entity);

        $class = $namespace->addClass($entity->getName());
        $class
            ->addExtend(AbstractEntity::class);

        if ($type === 'ods') {
            $class
                ->addComment("\n" .
                    str_replace('ZZ_ALIAS', $entity->getTable(),
                        str_replace('ZZ_TYPE_READ', $type,
                            str_replace('ZZ_TYPE_WRITE', $type, Entity::ANNOTATION_READ))
                        ) . "\n");
        } else {
            $class
                ->addComment("\n" .
                    str_replace('ZZ_ALIAS', $entity->getTable(),
                        str_replace('ZZ_TYPE_READ', $type,
                            str_replace('ZZ_TYPE_WRITE', $type,
                                str_replace('ZZ_ALIAS_WRITE', $entity->getTable(), Entity::ANNOTATION_WRITE)
                            )
                        )
                    ) . "\n"
                );
        }

        if ($fields !== []) {
            $properties = $entity->getProperties();

            $classProperties = [];

            foreach ($properties as $property) {
                if ($property->getIsKey() || in_array($property->getField(), $fields)) {
                    $classProperties[] = $property;
                }
            }

            $entity->setProperties($classProperties);
        }

        /** @var Property $property */
        foreach ($entity->getProperties() as $property){

            if ($property->getIsKey()){
                $class
                    ->addProperty($property->getName())
                    ->setVisibility(Property::PROPERTY_VISIBILITY)
                    ->addComment("\n" . str_replace('ZZ', $property->getField(),
                        str_replace('ZZ_DESC', $property->getDescription(),
                            str_replace('ZZ_TYPE', $property->getFieldType(),
                                str_replace('ZZ_QUOTES', $property->hasQuotes(),
                                    Property::PROPERTY_ANNOTATION_ISKEY)))) . "\n");
            } else {

                $annotation = $property->getFieldType() === 'choice' ? Property::PROPERTY_ANNOTATION_CHOICES : Property::PROPERTY_ANNOTATION;

                $choices = '';

                if (is_array($property->getChoices())) {
                    foreach ($property->getChoices() as $key => $value) {
                        $choices .= $value . '|' . $key . '#';
                    }
                    $choices = substr_replace($choices ,'', -1);
                } else {
                    $choices = $property->getChoices();
                }


                $class
                    ->addProperty($property->getName())
                    ->setVisibility(Property::PROPERTY_VISIBILITY)
                    ->addComment("\n" . str_replace('ZZ', $property->getField(),
                        str_replace('ZZ_DESC', $property->getDescription(),
                            str_replace('ZZ_TYPE', $property->getFieldType(),
                                str_replace('ZZ_QUOTES', $property->hasQuotes(),
                                    str_replace('ZZ_CHOICES', $choices,
                                        $annotation))))) . "\n");

            }

            $this->addGetter($property, $class);

            if ($type === 'sl' && !$property->getIsKey()) {
                $this->addSetter($property, $class);
            }

        }
        return $file;
    }

    private function addGetter(Property $property, ClassType $class)
    {
        $class->addMethod('get'.ucfirst($property->getName()))
            ->addBody('return $this->'.$property->getName().';');
    }

    private function addSetter(Property $property, ClassType $class)
    {
        $class->addMethod('set'.ucfirst($property->getName()))
            ->addBody('return $this->set(\''.$property->getName().'\', $'.$property->getName().');')
            ->addParameter($property->getName());
    }

    private function getBaseFile(): PhpFile
    {
        $file = new PhpFile();
        $file->addComment('This file is auto-generated by Boom.');
        $entityNamespace = $this->manager->config['app_namespace'].'\\HanaEntity';
        $namespace = $file->addNamespace($entityNamespace);
        $namespace
            ->addUse(EntityColumnMeta::class)
            ->addUse(EntityMeta::class);
        return $file;
    }

    private function getNamespace(PhpFile $file): PhpNamespace
    {
        $namespace = $file->getNamespaces();
        if (count($namespace) === 1){
            return array_shift($namespace);
        }
        throw new TransformationFailedException('Unable to find namespace for file');
    }
}