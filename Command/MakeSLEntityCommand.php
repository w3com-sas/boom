<?php

namespace W3com\BoomBundle\Command;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use W3com\BoomBundle\Generator\Model\Entity;
use W3com\BoomBundle\Generator\Model\Property;
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

        $SLInspector->initEntities();

        foreach ($SLInspector->getUDTEntities() as $UDTEntity) {
            $UDTsTable[] = $UDTEntity->getName();
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
            $io->section('Entity creation...');
            foreach ($SLInspector->getUDTEntities() as $UDTEntity) {
                if ($UDTEntity->getName() === $table) {
                    /** @var Entity $entity */
                    $entity = $UDTEntity;
                    break;
                }
            }

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

            $generator->createSLEntity($entity->getTable());

            $io->progressFinish();

            $io->success('Success in entity creation !');
        } else {
            $table = $io->choice("What's the name of the table ?", $SAPTables);

            foreach ($SLInspector->getSAPEntities() as $SAPEntity) {
                if ($SAPEntity->getTable() === $table) {
                    $entity = $SAPEntity;
                    break;
                }
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

                $alias = $io->ask("What is the key property name name?", lcfirst($entity->getKey()));

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
}