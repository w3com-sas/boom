<?php

namespace W3com\BoomBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->addOption(
                'force',
                null,
                InputOption::VALUE_OPTIONAL,
                'Force the current schema to update with "--force" argument.',
                false);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if ($input->getOption('force') === false) {

            $messages = $this->generator->inspectCurrentSchema();

            foreach ($messages as $message) {
                $output->writeln('<info>' . $message . '</info>', true);
            }
            $output->writeln('<info>Run boom:update-view --force to update your schema</info>');

        } else {

            try {
                $this->generator->createViewSchema();
            } catch (\Exception $e) {
                $output->write('<error>' . $e->getMessage() . '</error>', $e->getTraceAsString());
                die();
            }
            $output->writeLn('<info>Schema updated</info>');

        }
    }
}