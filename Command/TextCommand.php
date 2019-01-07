<?php

namespace W3com\BoomBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TextCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('boom:text-command');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output
            ->write('Hello');
    }
}