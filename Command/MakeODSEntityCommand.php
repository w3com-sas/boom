<?php

namespace W3com\BoomBundle\Command;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use W3com\BoomBundle\Generator\Model\Entity;
use W3com\BoomBundle\Service\BoomGenerator;

class MakeODSEntityCommand extends Command
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
            ->setName('boom:make:ods-entity')
            ->setDescription('Create HanaEntity through cv.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        AnnotationRegistry::registerLoader('class_exists');

        $io = new SymfonyStyle($input, $output);

        $calculationViews = [];

        $generator = $this->generator;

        $odsInspector = $generator->getOdsInspector();

        $odsInspector->initEntities();

        /** @var Entity $entity */
        foreach ($odsInspector->getEntities() as $entity) {
            $calculationViews[] = $entity->getTable();
        }
        sort($calculationViews);
        $io->title("Boom ODS Maker Command");
        $cv = $io->choice("What's the name of the cv ?", $calculationViews);

        foreach ($calculationViews as $calculationView){
            if ($calculationView === $cv){
                $entity = $cv;
                break;
            }
        }

        try {
            $this->generator->createODSEntity($entity);
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return 1;
        }
        $io->success($cv.' successfully created.');
        return 0;

    }
}