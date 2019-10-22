<?php

namespace W3com\BoomBundle\Generator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Symfony\Component\Form\Exception\TransformationFailedException;
use W3com\BoomBundle\Annotation\EntityColumnMeta;
use W3com\BoomBundle\Annotation\EntityMeta;
use W3com\BoomBundle\Annotation\EntitySynchronizedData;
use W3com\BoomBundle\Annotation\SynchronizedData;
use W3com\BoomBundle\HanaEntity\AbstractEntity;
use W3com\BoomBundle\Service\BoomGenerator;
use W3com\BoomBundle\Service\BoomManager;
use W3com\BoomBundle\Generator\Model\Entity;
use W3com\BoomBundle\Generator\Model\Property;
use W3com\BoomBundle\Utils\StringUtils;

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
            $entity = $this->generator->getSLInspector()->addMetaToEntity($entity);

            $comment = str_replace('ZZ_ALIAS', $entity->getTable(),
                        str_replace('ZZ_TYPE_READ', $type,
                            str_replace('ZZ_TYPE_WRITE', $type,
                                str_replace('ZZ_ALIAS_WRITE', $entity->getTable(), Entity::ANNOTATION_WRITE)
                            )
                        )
                    );

            if ($entity->isToSynchronize()) {
                $comment = str_replace('ZZ_SYNCHRO', 'true', $comment);
            } else {
                $comment = str_replace('ZZ_SYNCHRO', 'false', $comment);
            }

            $class
                ->addComment("\n" . $comment);
        }

        if ($entity->isToSynchronize()) {
            $synchAnnot = Entity::SYNCHRONIZE_ANNOTATION_BASE;
            $synchAnnot = str_replace('ZZ_TABLE_NAME', substr($entity->getTable(), 2), $synchAnnot);
            $synchAnnot = str_replace('ZZ_TABLE_DESCRIPTION', $entity->getDescription(), $synchAnnot);
            $synchAnnot = str_replace('ZZ_TABLE_TYPE', $entity->getType(), $synchAnnot);
            $synchAnnot = str_replace('ZZ_ARCHIVABLE', $entity->getArchivable(), $synchAnnot);

            if ($entity->getArchiveDate() !== null) {
                $synchAnnot .= Entity::SYNCHRONIZE_ANNOTATION_ARCHIVE_DATE;
                $synchAnnot = str_replace('ZZ_TABLE_ARCHIVE_DATE', $entity->getArchiveDate(), $synchAnnot);
            }

            $synchAnnot .= Entity::SYNCHRONIZE_ANNOTATION_END;
            $class->addComment($synchAnnot . "\n");
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

            $annotation = $this->createPropertyAnnotation($property);

            if ($property->isUDF()) {
                $annotation .= "\n" . $this->createSynchronizeAnnotation($property, $entity->getSapTable());
            }

            $var = $property->getVar();

            $class
                ->addProperty($property->getName())
                ->setVisibility(Property::PROPERTY_VISIBILITY)
                ->addComment("\n" . '@var ' . $var . "\n" . $annotation . "\n");

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
            ->addUse(SynchronizedData::class)
            ->addUse(EntitySynchronizedData::class)
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

    private function createPropertyAnnotation(Property $property)
    {
        $annotation = str_replace('ZZ_FIELD', $property->getField(), Property::PROPERTY_ANNOTATION_BASE);
        $annotation = str_replace('ZZ_DESC', $property->getDescription(), $annotation);
        $annotation = str_replace('ZZ_TYPE', $property->getFieldType(), $annotation);

        if ($property->isUDF()) {
            $annotation = str_replace('ZZ_SYNCHRO', 'true', $annotation);
        } else {
            $annotation = str_replace('ZZ_SYNCHRO', 'false', $annotation);
        }

        if (!$property->hasQuotes()) {
            $annotation .= Property::PROPERTY_ANNOTATION_QUOTES;
        }

        if ($property->getIsKey()) {
            $annotation .= Property::PROPERTY_ANNOTATION_IS_KEY;
        }

        if ($property->getChoices() !== []) {
            $annotation .= Property::PROPERTY_ANNOTATION_CHOICES;

            if (is_array($property->getChoices())) {
                $choices = StringUtils::choicesArrayToString($property->getChoices());
            } else {
                $choices = $property->getChoices();
            }

            $annotation = str_replace('ZZ_CHOICES', $choices, $annotation);
        }

        if ($property->getDefaultValue() !== null) {
            $annotation .= Property::PROPERTY_ANNOTATION_DEFAULT_VALUE;
            $annotation = str_replace('ZZ_DEFAULT_VALUE', $property->getDefaultValue(), $annotation);
        }

        if ($property->isMandatory()) {
            $annotation .= Property::PROPERTY_ANNOTATION_IS_MANDATORY;
        }

        $annotation .= Property::PROPERTY_ANNOTATION_END;

        return $annotation;
    }

    private function createSynchronizeAnnotation(Property $property, $table)
    {
        $annotation = str_replace('ZZ_COLUMN', substr($property->getField(), 2), Property::SYNCHRONIZE_ANNOTATION_BASE);
        $annotation = str_replace('ZZ_DESCRIPTION', $property->getDescription(), $annotation);
        $annotation = str_replace('ZZ_TYPE', $property->getFieldTypeMD(), $annotation);
        $annotation = str_replace('ZZ_SUBTYPE', $property->getFieldSubTypeMD(), $annotation);
        $annotation = str_replace('ZZ_TABLE', $table, $annotation);

        if ($property->getDefaultValue() !== null && $property->getDefaultValue() !== '') {
            $annotation .= Property::SYNCHRONIZE_ANNOTATION_DEFAULT_VALUE;
            $annotation = str_replace('ZZ_DEFAULT_VALUE', $property->getDefaultValue(), $annotation);
        }

        if ($property->getSize() !== null) {
            $annotation .= Property::SYNCHRONIZE_ANNOTATION_SIZE;
            $annotation = str_replace('ZZ_SIZE', $property->getSize(), $annotation);
        }

        if ($property->isMandatory()) {
            $annotation .= Property::SYNCHRONIZE_ANNOTATION_MANDATORY;
        }

        if ($property->getLinkedUDO() !== null) {
            $annotation .= Property::SYNCHRONIZE_ANNOTATION_LINKED_UDO;
            $annotation = str_replace('ZZ_LINKED_UDO', $property->getLinkedUDO(), $annotation);
        }

        if ($property->getLinkedSystemObject() !== null) {
            $annotation .= Property::SYNCHRONIZE_ANNOTATION_LINKED_SYSTEM_OBJECT;
            $annotation = str_replace('ZZ_LINKED_SYSTEM_OBJECT', $property->getLinkedSystemObject(), $annotation);
        }

        if ($property->getLinkedTable() !== null) {
            $annotation .= Property::SYNCHRONIZE_ANNOTATION_LINKED_TABLE;
            $annotation = str_replace('ZZ_LINKED_TABLE', $property->getLinkedTable(), $annotation);
        }

        if ($property->getChoices() !== []) {
            $annotation .= Property::SYNCHRONIZE_ANNOTATION_VALID_VALUES;

            if (is_array($property->getChoices())) {
                $choices = StringUtils::choicesArrayToString($property->getChoices());
            } else {
                $choices = $property->getChoices();
            }

            $annotation = str_replace('ZZ_VALID_VALUES', $choices, $annotation);
        }

        $annotation .= Property::SYNCHRONIZE_ANNOTATION_END;

        return $annotation;
    }
}