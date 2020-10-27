<?php

namespace W3com\BoomBundle\Command;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use W3com\BoomBundle\RestClient\OdataRestClient;
use W3com\BoomBundle\RestClient\SLRestClient;

class ClearCacheCommand extends Command
{
    private $toRemoveItems = [
        OdataRestClient::STORAGE_KEY,
        SLRestClient::STORAGE_KEY
    ];

    private $cache;

    public function __construct(AdapterInterface $cache)
    {
        $this->cache = $cache;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('boom:cl')
            ->setDescription('Clear boom cache.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        AnnotationRegistry::registerLoader('class_exists');

        $io = new SymfonyStyle($input, $output);

        if($this->cache->deleteItems($this->toRemoveItems)){
            $io->success('Cache was successfully cleared');
            return 0;
        } else {
            $io->error('Failed to clear cache');
            return 1;
        }
    }
}