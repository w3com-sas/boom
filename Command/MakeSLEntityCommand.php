<?php

namespace W3com\BoomBundle\Command;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use W3com\BoomBundle\Generator\AppInspector;
use W3com\BoomBundle\Generator\Model\Entity;
use W3com\BoomBundle\Generator\Model\Property;
use W3com\BoomBundle\Generator\SLInspector;
use W3com\BoomBundle\Service\BoomGenerator;

class MakeSLEntityCommand extends Command
{
    private $generator;

    public function __construct(BoomGenerator $generator)
    {
        $this->generator = $generator;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('boom:make:sl-entity')
            ->setDescription('Create HanaEntity with data in SAP');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        AnnotationRegistry::registerLoader('class_exists');

        $io = new SymfonyStyle($input, $output);

        $SAPTables = [];

        $UDTsTable = [];

        $generator = $this->generator;

        $SLInspector = $generator->getSLInspector();
        $AppInspector = $generator->getAppInspector();

        $SLInspector->initEntities();
        $AppInspector->initEntities();

        $appEntities = $AppInspector->getEntities();

        foreach ($SLInspector->getUDTEntities() as $UDTEntity) {
            $UDTsTable[] = $UDTEntity->getTable();
        }

        foreach ($SLInspector->getSAPEntities() as $SAPEntity) {
            $SAPTables[] = $SAPEntity->getTable();
        }

        $io->title("Boom Maker Command");

        $isUDT = $io->confirm("Entity is from an UDT ?");

        if ($isUDT) {
            $io->note("If you just created UDT, you must restart Service Layer on the server with the command : /etc/init.d/b1s restart \nThen, you must clear the boom cache with the command : bin/console boom:cl");
            $io->ask('Press enter to continue');
            $table = $io->choice("What's the name of the table?", $UDTsTable);

            $existingEntity = null;
            /** @var Entity $appEntity */
            foreach ($appEntities as $appEntity) {
                if ($table === $appEntity->getTable()) {
                    $existingEntity = $appEntity;
                    break;
                }
            }

            $action = 'Create';
            if ($existingEntity) {
                $action = $io->choice("An entity of " . $table . " exists in your project. Want you edit it or create a new entity?",
                    [
                        'Edit',
                        'Create'
                    ]
                );
            }

            foreach ($SLInspector->getUDTEntities() as $UDTEntity) {
                if ($UDTEntity->getTable() === $table) {
                    /** @var Entity $entity */
                    $entity = $UDTEntity;
                    break;
                }
            }
            if ($action === 'Create') {
                $io->section('Entity creation...');
                return $this->entityUDTCreation($io, $entity, $table);
            } else {
                return $this->entityEditing($io, $existingEntity, $entity);
            }

        } else {
            $table = $io->choice("What's the name of the table ?", $SAPTables);

            $existingEntity = null;
            /** @var Entity $appEntity */
            foreach ($appEntities as $appEntity) {
                if ($table === $appEntity->getTable() ||
                    'U_' . $table === $appEntity->getTable()) {
                    $existingEntity = $appEntity;
                    break;
                }
            }

            $action = 'create';
            if ($existingEntity) {
                $action = $io->choice("An entity of " . $table . " exists in your project. Want you edit it or create a new entity?",
                    [
                        'Edit',
                        'Create'
                    ]
                );
            }

            foreach ($SLInspector->getSAPEntities() as $slEntity) {
                if ($slEntity->getTable() === $table) {
                    /** @var Entity $slEntity */
                    $entity = $slEntity;
                    break;
                }
            }
            if ($action === 'Edit') {
                return $this->entityEditing($io, $existingEntity, $entity);
            }

            $alias = $io->ask("What is the class name?", $entity->getName());

            $entity->setAlias($alias);

            $properties = [$entity->getKey()];

            $propertiesChoices = [];

            /** @var Property $property */
            foreach ($entity->getProperties() as $property) {
                if (!$property->getIsKey()) {
                    $propertiesChoices[] = $property->getField();
                }
            }

            $allProperties = $io->confirm("Want you add all properties to your entity?", false);

            if ($allProperties) {
                $properties = [];

                $propertyAlias = $io->confirm('Want you set an alias to property?');

                while ($propertyAlias) {
                    $propertiesNames = [''];

                    /** @var Property $property */
                    foreach ($entity->getProperties() as $property) {
                        if (!$property->getAlias()) {
                            $propertiesNames[] = $property->getField();
                        }
                    }

                    $propertyToEdit = $io->choice('Which one?', $propertiesNames);

                    $alias = $io->ask("What is the property name?");
                    /** @var Property $property */
                    foreach ($entity->getProperties() as $property) {
                        if ($property->getField() === $propertyToEdit) {
                            $property->setAlias($alias);
                        }
                    }

                    $propertyAlias = $io->confirm('Want you set an alias to an other property?');
                }
            } else {
                $continue = true;

                $alias = $io->ask("What is the key property name?", lcfirst($entity->getKey()));

                /** @var Property $property */
                foreach ($entity->getProperties() as $property) {
                    if ($property->getIsKey()) {
                        $property->setAlias($alias);
                    }
                }

                while ($continue) {
                    $io->section('Properties of '.$entity->getTable().' Entity:');
                    $io->listing($properties);
                    $continue = $io->confirm("Want you add field in your entity?");
                    if ($continue) {
                        $newProperty = $io->choice("Which one?", $propertiesChoices);
                        $properties[] = $newProperty;
                        $alias = $io->ask("What is the property name?", lcfirst($newProperty));
                        /** @var Property $property */
                        foreach ($entity->getProperties() as $property) {
                            if ($property->getField() === $newProperty) {
                                $property->setAlias($alias);
                            }
                        }
                        unset($propertiesChoices[array_search($newProperty, $propertiesChoices)]);
                        $io->success($newProperty." added to the entity !");
                    }
                }
            }

            $io->section('Entity creation...');

            $io->progressStart(1);

            $generator->createSLEntity($entity->getTable(), $properties);
            $io->progressFinish();

            $io->success('Success in entity creation !');
        }
    }

    private function entityUDTCreation(SymfonyStyle $io, Entity $entity)
    {
        $alias = $io->ask("What is the class name?", $entity->getName());

        $entity->setAlias($alias);

        $propertyAlias = true;

        while ($propertyAlias) {
            $propertiesNames = [''];

            /** @var Property $property */
            foreach ($entity->getProperties() as $property) {
                if (!$property->getAlias()) {
                    $propertiesNames[] = $property->getField();
                }
            }

            $propertyToEdit = $io->choice('Want you set an alias to property? Which one? (Press return for no)', $propertiesNames);

            if ($propertyToEdit) {
                $alias = $io->ask("What is the property name?");
                /** @var Property $property */
                foreach ($entity->getProperties() as $property) {
                    if ($property->getField() === $propertyToEdit) {
                        $property->setAlias($alias);
                    }
                }
            } else {
                $propertyAlias = false;
            }
        }


        $io->progressStart(1);

        $this->generator->createSLEntity($entity->getTable());

        $io->progressFinish();

        $io->success('Success in entity creation !');

        return 1;
    }

    private function entityEditing(SymfonyStyle $io, Entity $appEntity, Entity $slEntity)
    {
        $actions = [
            'Add property',
            'Remove property'
        ];

        $entityPropertiesToAdd = $this->_getPropertiesChoiceToAdd($slEntity, $appEntity);
        $entityPropertiesToRemove = $this->_getPropertiesChoiceToRemove($appEntity);

        $propertiesToAdd = [];
        $propertiesToRemove = [];

        $action = true;
        while ($action) {
            if (in_array('Add property', $actions) && count($entityPropertiesToAdd) === 0) {
                unset($actions[0]);
            }
            if (in_array('Remove property', $actions) && count($entityPropertiesToRemove) === 0) {
                unset($actions[1]);
            }
            $action = $io->choice('What do you want to do?', $actions);
            switch ($action) {
                case 'Add property':
                    $propertyToAdd = $io->choice('What property want you add?', $entityPropertiesToAdd);
                    $alias = $io->ask("What is the property name?");
                    foreach ($entityPropertiesToAdd as $key => $value) {
                        if ($value === $propertyToAdd) {
                            unset($entityPropertiesToAdd[$key]);
                        }
                    }

                    /** @var Property $property */
                    foreach ($slEntity->getProperties() as $property) {
                        if ($property->getField() === $propertyToAdd) {
                            $property->setAlias($alias);
                            $propertiesToAdd[] = $property;
                        }
                    }

                    break;
                case 'Remove property':
                    $propertyToRemove = $io->choice('What property want you remove?', $entityPropertiesToRemove);
                    foreach ($entityPropertiesToRemove as $key => $value) {
                        if ($value === $propertyToRemove) {
                            unset($entityPropertiesToRemove[$key]);
                        }
                    }
                    /** @var Property $property */
                    foreach ($appEntity->getProperties() as $property) {
                        if ($property->getField() === $propertyToRemove) {
                            $propertiesToRemove[] = $property;
                        }
                    }
                    break;
            }

            $action = $io->confirm('Continue editing?', true);
        }
        $this->generator->addAndRemovePropertiesInAppEntity($propertiesToAdd, $propertiesToRemove, $appEntity);
    }

    private function _getPropertiesChoiceToAdd(Entity $slEntity, Entity $appEntity)
    {
        $appPropertiesFields = [];
        /** @var Property $appProperty */
        foreach ($appEntity->getProperties() as $appProperty) {
            $appPropertiesFields[] = $appProperty->getField();
        }
        $entityPropertiesToAdd = [];
        /** @var Property $property */
        foreach ($slEntity->getProperties() as $property) {
            if (!in_array($property->getField(), $appPropertiesFields)) {
                $entityPropertiesToAdd[] = $property->getField();
            }
        }

        return $entityPropertiesToAdd;
    }

    private function _getPropertiesChoiceToRemove(Entity $appEntity)
    {
        $appPropertiesFields = [];
        /** @var Property $appProperty */
        foreach ($appEntity->getProperties() as $appProperty) {
            if (!$appProperty->getIsKey() && $appProperty->getField()) {
                $appPropertiesFields[] = $appProperty->getField();
            }
        }

        return $appPropertiesFields;
    }
}