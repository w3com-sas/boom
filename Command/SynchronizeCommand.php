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
use W3com\BoomBundle\HanaEntity\UserFieldsMD;
use W3com\BoomBundle\HanaEntity\UserTablesMD;
use W3com\BoomBundle\HanaRepository\UserFieldsMDRepository;
use W3com\BoomBundle\Service\BoomGenerator;
use W3com\BoomBundle\Service\BoomManager;

class SynchronizeCommand extends Command
{
    private $generator;
    private $manager;

    public function __construct(BoomGenerator $generator, BoomManager $manager)
    {
        $this->generator = $generator;
        $this->manager = $manager;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('boom:synchronize')
            ->setDescription('Synchronize entities in project with tables and system objects in SAP.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        AnnotationRegistry::registerLoader('class_exists');

        $appInspector = $this->generator->getAppInspector();

        $io = new SymfonyStyle($input, $output);

        $appInspector->initEntities();

        $io->title("Boom Synchronize Command");

//        $all = $io->confirm("Want you synchronize all the project ?", false);
        $all = true;

        $listEntities = [];
        $listProperties = [];

        /** @var Entity $entity */
        foreach ($appInspector->getEntities() as $entity) {
            if ($entity->isToSynchronize()) {
                $listEntities[] = $entity;
            }
            /** @var Property $property */
            foreach ($entity->getProperties() as $property) {
                if ($property->isUDF()) {
                    $listProperties[] = $property;
                }
            }
        }


        if ($all) {
            $io->title('Entities creation...');
            $io->progressStart(count($listEntities));

            foreach ($listEntities as $entity) {
                $this->entityCreation($entity);
                $io->progressAdvance(1);
            }

            $io->progressFinish();

            $io->title('Properties creation...');
            $io->progressStart(count($listProperties));

            foreach ($listProperties as $property) {
                $this->propertyCreation($property);
                $io->progressAdvance(1);
            }

            $io->progressFinish();
        } else {
//            /** @var Entity $entity */
//            foreach ($appInspector->getEntities() as $entity) {
//                if ($entity->isToSynchronize()) {
//                    $listEntities[] = $entity;
//                }
//                /** @var Property $property */
//                foreach ($entity->getProperties() as $property) {
//                    if ($property->isUDF()) {
//                        $listProperties[] = $property;
//                    }
//                }
//            }
        }
    }

    private function entityCreation(Entity $entity)
    {
        $udtRepo = $this->manager->getRepository('UserTablesMD');

        $exists = $udtRepo->find(substr($entity->getTable(), 2));

        if ($exists) {
            return;
        }

        $udt = new UserTablesMD();

        $udt->setTableName(substr($entity->getTable(), 2));
        $udt->setTableType($entity->getType());
        $udt->setTableDescription($entity->getDescription());
        $udt->setArchivable($entity->getArchivable());

        if ($entity->getArchiveDate() !== '' && $entity->getArchiveDate() !== null) {
            $udt->setArchiveDateField($entity->getArchiveDate());
        }

        $udtRepo->add($udt);
    }

    private function propertyCreation(Property $property)
    {
        /** @var UserFieldsMDRepository $udfRepo */
        $udfRepo = $this->manager->getRepository('UserFieldsMD');

        $exists = $udfRepo->findByTableNameAndFieldName($property->getSapTable(), $property->getField());

        dump($property->getSapTable(), $property->getField());

        if ($exists) {
            return;
        } else {
            $exists = $udfRepo->findByTableNameAndFieldName($property->getSapTable(), strtolower($property->getField()));
            if ($exists) {
                return;
            }
        }

        $udf = new UserFieldsMD();

        $udf->setTableName($property->getSapTable());
        $udf->setType($property->getFieldTypeMD());
        $udf->setSubType($property->getFieldSubTypeMD());
        $udf->setDescription($property->getDescription());
        $udf->setEditSize($property->getSize());
        $udf->setName(str_replace('u_', '', str_replace('U_', '', $property->getName())));

        if ($property->isMandatory()) {
            $udf->setMandatory('tYES');
        }

        if ($property->getLinkedSystemObject() !== null && $property->getLinkedSystemObject() !== '') {
            $udf->setLinkedSystemObject($property->getLinkedSystemObject());
        }

        if ($property->getLinkedUDO() !== null && $property->getLinkedUDO() !== '') {
            $udf->setLinkedUDO($property->getLinkedUDO());
        }

        if ($property->getLinkedTable() !== null && $property->getLinkedTable() !== '') {
            $udf->setLinkedTable($property->getLinkedTable());
        }

        $retry = true;

        $nbTour = 0;
        while ($retry && $nbTour < 5) {
            try {
                $udfRepo->add($udf);
                $retry = false;
            } catch (\Exception $e) {
                $nbTour++;
            }
        }
    }
}