<?php

namespace W3com\BoomBundle\Command;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use W3com\BoomBundle\Service\BoomManager;

class UpdateViewCommand extends Command
{
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
            ->setDescription('Update schema with calculation view metadata.')
            ->setHelp('In first time the command check and return difference between 
            current project schema and ODS metadata. When the argument "--force" is 
            added, the generator update current schema, same as Doctrine.')
            ->addArgument('force', InputArgument::OPTIONAL,
                'Force the current schema to update with "--force" argument.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        AnnotationRegistry::registerLoader('class_exists');

        if ($input->getArgument('force') === null) {

            $messages = $this->generator->inspectCurrentSchema();

            foreach ($messages as $message) {
                $output->write('<info>' . $message . '</info>', true);
            }
            $output->writeln('<info>Run boom:update-view --force to update your schema</info>');

        } elseif ($input->getArgument('force') == '--force') {

            try {
                $this->generator->updateViewSchema();
            } catch (\Exception $e){
                $output->write('<error>'.$e->getMessage().'</error>', $e->getTraceAsString());
                die();
            }
            $output->write('<success>Schema updated</success>');

        } else {
            $output->write('Unknow action ' . $input->getArgument('force'));
        }
    }
}