<?php

namespace W3com\BoomBundle\Command;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use W3com\BoomBundle\Generator\AppInspector;
use W3com\BoomBundle\RestClient\OdataRestClient;
use W3com\BoomBundle\RestClient\SLRestClient;

class ClearCacheCommand extends Command
{
    private $toRemoveItems = [
        OdataRestClient::STORAGE_KEY,
        SLRestClient::STORAGE_KEY,
    ];

    private $cache;

    private $arrayCache;

    public function __construct(AdapterInterface $cache)
    {
        $this->cache = $cache;
        $this->arrayCache = new PhpArrayAdapter(
            substr(AppInspector::ENTITIES_CACHE_DIRECTORY, 1).AppInspector::ENTITIES_CACHE_KEY.'.cache',
            new FilesystemAdapter()
        );
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('boom:clear')
            ->setDescription('Clear boom cache.');
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        AnnotationRegistry::registerLoader('class_exists');

        $io = new SymfonyStyle($input, $output);

        if($this->cache->deleteItems($this->toRemoveItems) && $this->arrayCache->clear()){
            $io->success('Cache was successfully cleared');
            return 0;
        } else {
            $io->error('Failed to clear cache');
            return 1;
        }
    }
}