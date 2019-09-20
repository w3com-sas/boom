<?php

namespace W3com\BoomBundle\Command;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use W3com\BoomBundle\Generator\Model\Entity;
use W3com\BoomBundle\Generator\Model\Property;
use W3com\BoomBundle\HanaEntity\FieldDefinition;
use W3com\BoomBundle\Service\BoomGenerator;

class MakeEntityCommand extends Command
{
    private $createdEntities = [];

    private $odsEntities = [];

    private $generator;

    public function __construct(BoomGenerator $generator)
    {
        $this->generator = $generator;
        parent::__construct();
    }

    protected function configure()
    {

        $this
            ->setName('boom:maker')
            ->setDescription('Create HanaEntity with data in SAP');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        AnnotationRegistry::registerLoader('class_exists');

        $io = new SymfonyStyle($input, $output);

        $io->title("Welcome in the Boom Maker Command");

        $SAPTables = [];

        $UDTsTable = [];

        $generator = $this->generator;

        $SLInspector = $generator->getSLInspector();

        foreach ($SLInspector->getUDTEntities() as $UDTEntity) {
            $UDTsTable[] = $UDTEntity->getName();
        }

        foreach ($SLInspector->getSAPEntities() as $SAPEntity) {
            $SAPTables[] = $SAPEntity->getTable();
        }

        $isUDT = $io->confirm("Entity is from an UDT ?");


        if ($isUDT) {
            $table = $io->choice("What's the name of the table ?", $UDTsTable);
            $io->section('Entity creation...');
            foreach ($SLInspector->getUDTEntities() as $UDTEntity) {
                if ($UDTEntity->getName() === $table) {
                    /** @var Entity $entity */
                    $entity = $UDTEntity;
                    break;
                }
            }

            $fieldRepo = $this->generator->getManager()->getRepository('FieldDefinition');

            $percent = count($entity->getProperties());
            $io->progressStart($percent);

            /** @var Property $property */
            foreach ($entity->getProperties() as $property) {
                if (strpos(strtolower($property->getField()), 'u_w3c') !== false
                    && strpos(strtolower($entity->getTable()), 'w3c') !== false) {

                        /** @var FieldDefinition $fieldDefinition */
                        $fieldDefinition = $fieldRepo->find('@' . substr($entity->getTable(),2) . '_' . $property->getField());

                        if ($fieldDefinition !== null) {
                            $property->setDescription($fieldDefinition->getDescription());
                        } else {
                            $property->setDescription($property->getField());
                        }
                } else {
                    $property->setDescription($property->getField());
                }
                $io->progressAdvance(1);
            }

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

            $continue = true;

            while ($continue) {
                $io->writeln('Properties of '.$entity->getTable().' Entity :');
                $io->listing($properties);
                $continue = $io->confirm("Want you add field in your entity ?");
                if ($continue) {
                    $newProperty = $io->choice("Wich one ?", $propertiesChoices);
                    $properties[] = $newProperty;
                    unset($propertiesChoices[array_search($newProperty, $propertiesChoices)]);
                    $io->success($newProperty." added to the entity !");
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