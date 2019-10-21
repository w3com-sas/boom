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

class SynchronizeCommand extends Command
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
            ->setName('boom:synchronize')
            ->setDescription('Create HanaEntity with data in SAP');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //TODO
    }
}