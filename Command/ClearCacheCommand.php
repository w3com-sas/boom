<?php

namespace W3com\BoomBundle\Command;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;
use W3com\BoomBundle\Generator\AppInspector;
use W3com\BoomBundle\RestClient\OdataRestClient;
use W3com\BoomBundle\RestClient\SLRestClient;

class ClearCacheCommand extends Command
{
    const HULK_BUNDLE_NAME = 'W3comHulkBundle';

    private $toRemoveItems = [
        OdataRestClient::STORAGE_KEY,
        SLRestClient::STORAGE_KEY,
    ];

    /**
     * @var FilesystemAdapter
     */
    private $cache;

    /**
     * @var PhpArrayAdapter
     */
    private $arrayCache;

    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct( KernelInterface $kernel)
    {
        $this->cache = new FilesystemAdapter();
        $this->kernel = $kernel;
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
            ->setDescription('Clear Boom cache.')
            ->addArgument('isFromHulk', InputArgument::OPTIONAL, 'If this command is run from Hulk bundle.')
        ;
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        AnnotationRegistry::registerLoader('class_exists');

        $io = new SymfonyStyle($input, $output);
        $returnValue = 0;
        $isFromHulk = false;

        if ($input->getArgument('isFromHulk') !== null) {
            $isFromHulk = $input->getArgument('isFromHulk');
        }

        if ($isFromHulk) {
            if($this->cache->deleteItems($this->toRemoveItems) && $this->arrayCache->clear()){
                $io->success('Boom cache successfully cleared.');
                $returnValue = 0;
            } else {
                $io->error('Failure during clear Boom cache.');
                $returnValue = 1;
            }
        } else {
            $bundles = $this->kernel->getBundles();
            $found = false;

            /** @var Bundle $bundle */
            foreach ($bundles as $bundle) {
                if ($bundle->getName() === self::HULK_BUNDLE_NAME) {
                    $found = true;
                    $io->success('Hulk bundle exists in this project, hulk:clear will be run instead.');
                    $command = $this->getApplication()->find('hulk:clear');
                    $command->run($input, $output);
                    $returnValue = 0;
                }
            }

            if (!$found) {
                if($this->cache->deleteItems($this->toRemoveItems) && $this->arrayCache->clear()){
                    $io->success('Boom cache successfully cleared.');
                    $returnValue = 0;
                } else {
                    $io->error('Failure during clear Boom cache.');
                    $returnValue = 1;
                }
            }
        }

        return $returnValue;
    }
}
