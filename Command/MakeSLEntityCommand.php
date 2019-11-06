<?php

namespace W3com\BoomBundle\Command;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use W3com\BoomBundle\Generator\Model\Entity;
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
            $table = $io->choice("What's the name of the table ?", $UDTsTable);
            $io->section('Entity creation...');
            foreach ($SLInspector->getUDTEntities() as $UDTEntity) {
                if ($UDTEntity->getName() === $table) {
                    /** @var Entity $entity */
                    $entity = $UDTEntity;
                    break;
                }
            }

            $io->progressStart(1);

            $generator->createSapEntity($entity->getTable());

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

            $properties = [$entity->getKey()];

            $propertiesChoices = [];

            foreach ($entity->getProperties() as $property) {
                if (!$property->getIsKey()) {
                    $propertiesChoices[] = $property->getField();
                }
            }

            $allProperties = $io->confirm("Want you add all properties to your entity ?", false);

            if ($allProperties) {
                foreach ($propertiesChoices as $property) {
                    $properties[] = $property;
                }
            } else {
                $continue = true;

                while ($continue) {
                    $io->section('Properties of '.$entity->getTable().' Entity :');
                    $io->listing($properties);
                    $continue = $io->confirm("Want you add field in your entity ?");
                    if ($continue) {
                        $newProperty = $io->choice("Which one ?", $propertiesChoices);
                        $properties[] = $newProperty;
                        unset($propertiesChoices[array_search($newProperty, $propertiesChoices)]);
                        $io->success($newProperty." added to the entity !");
                    }
                }
            }

            $io->section('Entity creation...');

            $io->progressStart(1);

            $generator->createSapEntity($entity->getTable(), $properties);
            $io->progressFinish();

            $io->success('Success in entity creation !');
        }
    }
}