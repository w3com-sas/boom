<?php

namespace W3com\BoomBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use W3com\BoomBundle\Generator\Model\Entity;
use W3com\BoomBundle\Service\BoomManager;

class UpdateViewCommand extends Command
{
    private $createdEntities = [];

    private $odsEntities = [];

    private $generator;

    public function __construct(BoomManager $manager)
    {
        $this->generator = $manager->getGenerator();
        parent::__construct();
    }

    protected function configure()
    {

        $this
            ->setName('boom:update-view')
            ->setDescription('Update schema, user select calculation that he wants.')
            ->setHelp('If no arguments are provided, the command return list of 
            calculation views in services.xsodata, else, command create views provided
            in argument, the separator is an escape.')
            ->addArgument(
                'entity',
                InputOption::VALUE_OPTIONAL,
                'Add the name of the CalculationView.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $io = new SymfonyStyle($input, $output);

        if (empty($input->getArgument('entity'))) {

            $entities = $this->generator->getOdsInspector()->getOdsEntities();

            $io->title('Calculation view list : ');

            /** @var Entity $entity */
            foreach ($entities as $entity) {
                $this->odsEntities[] = $entity->getTable().' - '.count($entity->getProperties()).' field(s)';
            }
            $io->listing($this->odsEntities);
            $io->title('Run "boom:update-view $entityName $secondEntityIfYouWant .." to update your schema');

        } else {

            $entities = $input->getArgument('entity');

            foreach ($entities as $entity){

                try {
                    $this->createdEntities[$entity] = $entity;
                    $this->generator->createViewEntity($entity);
                } catch (NotFoundResourceException $e) {
                    $io->error($e->getMessage());
                    unset($this->createdEntities[$entity]);
                    die();
                } catch (\Exception $e){
                    $io->error($e->getMessage().$e->getTraceAsString());
                    unset($this->createdEntities[$entity]);
                    die();
                }

            }

            $io->success('Entity created');
            $io->listing($entities);
        }
    }
}