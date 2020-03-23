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

    public function generateClass(Entity $entity, $type = 'ods', $fields = [], $isComplex = false)
    {
        $file = $this->getBaseFile();
        $namespace = $this->getNamespace($file);

        $className = $entity->getAlias() ? $entity->getAlias() : $entity->getName();
        $class = $namespace->addClass($className);
        $class
            ->addExtend(AbstractEntity::class);

        if ($type === 'ods') {
            $class
                ->addComment("\n" .
                    str_replace('ZZ_ALIAS', $entity->getTable(),
                        str_replace('ZZ_TYPE_READ', $type,
                            str_replace('ZZ_TYPE_WRITE', $type, Entity::ANNOTATION_READ)
                        )
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

            if ($isComplex) {
                $comment = str_replace('ZZ_COMPLEX', 'true', $comment);
            } else {
                $comment = str_replace('ZZ_COMPLEX', 'false', $comment);
            }

            $class
                ->addComment("\n" . $comment);
        }

        if ($entity->isToSynchronize()) {
            $synchAnnot = Entity::SYNCHRONIZE_ANNOTATION_BASE;
            $synchAnnot = str_replace('ZZ_TABLE_NAME', str_replace('U_', '', $entity->getTable()), $synchAnnot);
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

            $class = $this->createPropertyInClass($property, $entity, $class);

            $this->addGetter($property, $class);

            if ($type === 'sl') {
                $this->addSetter($property, $class);
            }

        }

        file_put_contents($this->manager->config['entity_directory'] . '/'
            . $className . '.php', $file);
    }

    private function addGetter(Property $property, ClassType $class)
    {
        $propertyName = $property->getAlias() ? $property->getAlias() : $property->getName();
        $class->addMethod('get'.ucfirst($propertyName))
            ->addBody('return $this->'.$propertyName.';');
    }

    private function addSetter(Property $property, ClassType $class)
    {
        $propertyName = $property->getAlias() ? $property->getAlias() : $property->getName();
        $class->addMethod('set'.ucfirst($propertyName))
            ->addBody('return $this->set(\''.$propertyName.'\', $'.$propertyName.');')
            ->addParameter($propertyName);
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

        if ($property->getComplexEntity() !== null) {
            $annotation .= Property::PROPERTY_ANNOTATION_COMPLEX_ENTITY;
            $annotation = str_replace('ZZ_COMPLEX', $property->getComplexEntity()->getName(), $annotation);
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

    public function addAndRemovePropertiesToExistingClass($propertiesToAdd, $propertiesToRemove, Entity $entity)
    {
        foreach ($propertiesToAdd as $property) {
            $entity->addProperty($property);
        }
        foreach ($propertiesToRemove as $property) {
            $entity->removeProperty($property);
        }
        $entity = $this->generator->getSLInspector()->addMetaToEntity($entity);

        [$class, $file] = $this->getExistingClassTypeWithoutProperties($entity->getName(), $propertiesToRemove);

        foreach ($propertiesToAdd as $property) {
            $class = $this->createPropertyInClass($property, $entity, $class);

            $this->addGetter($property, $class);
            $this->addSetter($property, $class);
        }


        file_put_contents($this->manager->config['entity_directory'] . '/'
            . $entity->getName() . '.php', $file);
    }

    private function getExistingClassTypeWithoutProperties($name, $properties)
    {
        $baseClass = ClassType::from($this->manager->config['app_namespace'].'\\HanaEntity\\' . $name);
        $file = $this->getBaseFile();
        $namespace = $this->getNamespace($file);

        $class = $namespace->addClass($name);
        $class
            ->addExtend(AbstractEntity::class);

        $class->addComment($baseClass->getComment());
        $class->setConstants($baseClass->getConstants());
        $propertiesToSet = [];
        foreach ($baseClass->getProperties() as $propertyMeta) {
            /** @var Property $propertyToRemove */
            foreach ($properties as $propertyToRemove) {
                if ($propertyToRemove->getName() === $propertyMeta->getName()) {
                    continue 2;
                }
            }
            $propertiesToSet[] = $propertyMeta;
        }
        $class->setProperties($propertiesToSet);

        $methodsToSet = [];
        foreach ($baseClass->getMethods() as $method) {
            foreach ($properties as $propertyToRemove) {
                $propertyName = $propertyToRemove->getName();
                if (strpos($method->getName(), ucfirst($propertyName))) {
                    continue 2;
                }
            }
            $method->setBody(
                $this->getMethodBody(
                    $this->manager->config['app_namespace'].'\\HanaEntity\\' . $name,
                    $method->getName()
                )
            );
            $methodsToSet[] = $method;
        }
        $class->setMethods($methodsToSet);

        return [$class, $file];
    }

    private function createPropertyInClass(Property $property, Entity $entity, ClassType $class)
    {
        if ($property->getComplexEntity() !== null) {
            $this->generateClass($property->getComplexEntity(), 'sl', [], true);
        }

        $annotation = $this->createPropertyAnnotation($property);

        if ($property->isUDF()) {
            $annotation .= "\n" . $this->createSynchronizeAnnotation($property, $entity->getSapTable());
        }

        $var = $property->getVar();

        $propertyName = $property->getAlias() ? $property->getAlias() : $property->getName();

        $class
            ->addProperty($propertyName)
            ->setVisibility(Property::PROPERTY_VISIBILITY)
            ->addComment("\n" . '@var ' . $var . "\n" . $annotation . "\n");

        return $class;
    }

    private function getMethodBody($class, $method)
    {
        $func = new \ReflectionMethod($class, $method);

        $filename = $func->getFileName();
        $start_line = $func->getStartLine() +1; // it's actually - 1, otherwise you wont get the function() block
        $end_line = $func->getEndLine() - 1;
        $length = $end_line - $start_line;

        $source = file($filename);
        $lines = array_slice($source, $start_line, $length);

        foreach ($lines as $key => $line) {
            $lines[$key] = trim($line);
        }

        $body = implode("\n", $lines);

        return $body;
    }
}