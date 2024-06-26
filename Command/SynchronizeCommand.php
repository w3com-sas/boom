<?php

namespace W3com\BoomBundle\Command;

use Exception;
use Psr\Cache\InvalidArgumentException;
use ReflectionException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use W3com\BoomBundle\Generator\AppInspector;
use W3com\BoomBundle\Generator\Model\Entity;
use W3com\BoomBundle\Generator\Model\Property;
use W3com\BoomBundle\HanaEntity\UserFieldsMD;
use W3com\BoomBundle\HanaEntity\UserTablesMD;
use W3com\BoomBundle\HanaRepository\UserFieldsMDRepository;
use W3com\BoomBundle\Service\BoomGenerator;
use W3com\BoomBundle\Service\BoomManager;

class SynchronizeCommand extends Command
{
    /**
     * @var BoomGenerator
     */
    private $generator;

    /**
     * @var BoomManager
     */
    private $manager;

    /**
     * @var PhpArrayAdapter
     */
    private $cache;

    public function __construct(BoomGenerator $generator, BoomManager $manager)
    {
        $this->generator = $generator;
        $this->manager = $manager;
        $this->cache = new PhpArrayAdapter(
            AppInspector::ENTITIES_CACHE_DIRECTORY.AppInspector::ENTITIES_CACHE_KEY.'.cache',
            new FilesystemAdapter()
        );
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('boom:synchronize')
            ->setDescription('Synchronize entities in project with tables and system objects in SAP.')
        ;
    }

    /**
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $appInspector = $this->generator->getAppInspector();
        $io = new SymfonyStyle($input, $output);
        $cacheItem = $this->cache->getItem(AppInspector::ENTITIES_CACHE_KEY);

        if (empty($cacheItem->get())) {
            $appInspector->initEntities();
        }

        $io->title("Boom Synchronize Command");
        $all = $io->confirm("Want you synchronize all the project ?", false);
        $listEntities = [];
        $listProperties = [];
        $entitiesHavingPropertiesToSync = [];

        /** @var Entity $entity */
        foreach ($cacheItem->get() as $entity) {
            if ($entity->isToSynchronize()) {
                $listEntities[] = $entity;
            }

            /** @var Property $property */
            foreach ($entity->getProperties() as $property) {
                if ($property->isUDF()) {
                    if (!in_array($entity, $entitiesHavingPropertiesToSync)) {
                        $entitiesHavingPropertiesToSync[] = $entity;
                    }
                    $listProperties[] = $property;
                }
            }
        }

        if ($all) {
            $io->title('Entities creation...');
            $io->progressStart(count($listEntities));
            $nbEntitiesCreated = 0;

            foreach ($listEntities as $entity) {
                $created = $this->entityCreation($entity);
                if ($created) {
                    $nbEntitiesCreated++;
                }
                $io->progressAdvance();
            }

            $io->progressFinish();

            if ($nbEntitiesCreated === 0) {
                $io->success('Entities are already up to date!');
            } else {
                $io->success($nbEntitiesCreated . ' entity(ies) created!');
            }

            $io->title('Properties creation...');
            $io->progressStart(count($listProperties));
            $nbPropertiesCreated = 0;

            foreach ($listProperties as $property) {
                $created = $this->propertyCreation($property, $io);
                if ($created) {
                    $nbPropertiesCreated++;
                }
                $io->progressAdvance();
            }

            $io->progressFinish();

            if ($nbPropertiesCreated === 0) {
                $io->success('Properties are already up to date!');
            } else {
                $io->success($nbPropertiesCreated . ' property(ies) created!');
            }
        } else {
            $listEntitiesName = array_map([$this, "nameOfEntity"], $entitiesHavingPropertiesToSync);
            $entityToSynch = $io->choice('Which Entity', $listEntitiesName);
            foreach ($entitiesHavingPropertiesToSync as $entity) {
                if ($entity->getName() === $entityToSynch) {
                    if ($entity->isToSynchronize()) {
                        $created = $this->entityCreation($entity);

                        if ($created) {
                            $io->success('Entity ' . $entity->getName() . ' synchronized!');
                        } else {
                            $io->success('Entity is already synchronized!');
                        }
                    }
                    foreach ($entity->getProperties() as $property) {
                        if ($property->isUDF()) {
                            $created = $this->propertyCreation($property, $io);

                            if ($created) {
                                $io->success('Field ' . $property->getField() . ' synchronized!');
                            }
                        }
                    }
                    break;
                }
            }
        }
        return 0;
    }

    private function nameOfEntity(Entity $entity)
    {
        return $entity->getName();
    }

    /**
     * @throws Exception
     */
    private function entityCreation(Entity $entity): bool
    {
        $udtRepo = $this->manager->getRepository('UserTablesMD');
        $exists = $udtRepo->find(str_replace('U_', '', $entity->getTable()));

        if ($exists) {
            return false;
        }

        $udt = new UserTablesMD();
        $udt->setTableName(str_replace('U_', '', $entity->getTable()));
        $udt->setTableType($entity->getType());
        $udt->setTableDescription($entity->getDescription());
        $udt->setArchivable($entity->getArchivable());

        if ($entity->getArchiveDate() !== '' && $entity->getArchiveDate() !== null) {
            $udt->setArchiveDateField($entity->getArchiveDate());
        }

        $udtRepo->add($udt);

        return true;
    }

    /**
     * @throws Exception
     */
    private function propertyCreation(Property $property, SymfonyStyle $io): bool
    {
        /** @var UserFieldsMDRepository $udfRepo */
        $udfRepo = $this->manager->getRepository('UserFieldsMD');
        $exists = $udfRepo->findByTableNameAndFieldName(
            $property->getSapTable(),
            str_replace('u_', '', str_replace('U_', '', $property->getField()))
        );

        if ($exists) {
            return false;
        } else {
            $exists = $udfRepo->findByTableNameAndFieldName(
                $property->getSapTable(),
                str_replace(
                    'u_', '',
                    str_replace('U_', '', strtolower($property->getField()))
                )
            );
            if ($exists) {
                return false;
            }
        }

        $udf = new UserFieldsMD();
        $udf->setTableName($property->getSapTable());
        $udf->setType($property->getFieldTypeMD());
        $udf->setSubType($property->getFieldSubTypeMD());
        $udf->setDescription($property->getDescription());
        $udf->setName(
            str_replace('u_', '', str_replace('U_', '', $property->getField()))
        );

        if ($property->isMandatory()) {
            $udf->setMandatory('tYES');
        }

        if ($property->getSize()) {
            $udf->setEditSize($property->getSize());
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

        if ($property->getChoices() !== [] && $property->getChoices() !== null) {
            $udf->setValidValuesMD($property->getChoices());
        }

        $retry = true;
        $nbTour = 0;

        while ($retry && $nbTour < 5) {
            try {
                $udfRepo->add($udf);
                $retry = false;
            } catch (Exception $e) {
                $nbTour++;
                if ($nbTour === 5) {
                    $io->error(
                        'An error occurred during creation of '.$property->getName()
                        .' field in '.$property->getTable().' entity : '.$e->getMessage())
                    ;

                    return false;
                }
            }
        }

        return true;
    }
}