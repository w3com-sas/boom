<?php

namespace W3com\BoomBundle\Service;

use Doctrine\Common\Annotations\AnnotationReader;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use ReflectionClass;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;
use W3com\BoomBundle\Repository\AbstractRepository;
use W3com\BoomBundle\Repository\DefaultRepository;
use W3com\BoomBundle\Repository\RepoMetadata;
use W3com\BoomBundle\RestClient\OdataRestClient;
use W3com\BoomBundle\RestClient\SLRestClient;

class BoomManager
{
    /**
     * @var string current connection key (ex: default, connection1), as defined in config
     */
    private $currentConnection;

    /**
     * @var array already loaded Guzzle clients
     */
    private $clients = [];

    /**
     * @var array already loaded repositories
     */
    private $repositories = [];

    /**
     * @var array BOOM configuration
     */
    public $config;

    /**
     * @var array Rest clients (SL and ODS)
     */
    public $restClients = [];

    /**
     * @var AnnotationReader
     */
    private $reader;

    /**
     * @var Logger linked to 'hana' channel
     */
    public $logger;

    /**
     * @var null|Stopwatch
     */
    public $stopwatch;

    /**
     * @var array
     */
    private $collectedData;

    /**
     * BoomManager constructor.
     *
     * @param array $config
     * @param Logger $logger
     * @param Stopwatch|null $stopwatch
     *
     * @throws \Exception
     */
    public function __construct($config, Logger $logger, Stopwatch $stopwatch)
    {
        $this->config = $config;
        $this->reader = new AnnotationReader();
        $this->logger = $logger;
        $this->stopwatch = $stopwatch;

        // creating cookie jar if needed
        $fs = new Filesystem();
        $fs->mkdir($config['service_layer']['cookies_storage_path']);

        // creating the ODS client
        $jar = new FileCookieJar($config['service_layer']['cookies_storage_path'].'/odata', true);
        $client = new Client(
            [
                'cookies' => $jar,
                'base_uri' => $config['odata_service']['base_uri'].'/'.$config['odata_service']['path'],
                'verify' => $config['odata_service']['verify_https'],
            ]
        );
        $this->clients['odata'] = $client;

        // creating the default SL connection
        $this->setCurrentConnection('default');

        // creating rest clients
        $slRestClient = new SLRestClient($this);
        $this->restClients['sl'] = $slRestClient;
        $odataRestClient = new OdataRestClient($this);
        $this->restClients['odata'] = $odataRestClient;
    }

    public function getCollectedData()
    {
        return $this->collectedData;
    }

    public function addToCollectedData($type, $code, $uri, $params, $response, $stop = null)
    {
        $data = [
            'type' => $type,
            'code' => $code,
            'uri' => $uri,
            'parameters' => $params,
            'response' => $response,
        ];
        if ($stop instanceof StopwatchEvent) {
            $data['duration'] = $stop->getDuration();
        }
        $decoded = json_decode($response);
        if (is_object($decoded)) {
            $data['decoded'] = $decoded;
        }
        $this->collectedData[] = $data;
    }

    /**
     * @return string
     */
    public function getCurrentConnection()
    {
        return $this->currentConnection;
    }

    /**
     * @return Client
     */
    public function getCurrentClient()
    {
        return $this->clients[$this->currentConnection];
    }

    /**
     * @return Client
     */
    public function getOdsClient()
    {
        return $this->clients['odata'];
    }

    /**
     * @param $connection
     *
     * @return BoomManager
     *
     * @throws \Exception
     *
     * @internal param mixed $currentConnection
     */
    public function setCurrentConnection($connection)
    {
        if (!array_key_exists($connection, $this->config['service_layer']['connections'])) {
            throw new \Exception("Unknown $connection connection. Check configuration.");
        }

        $this->currentConnection = $connection;

        if (!array_key_exists($connection, $this->clients)) {
            // creating the cookie jar
            $jar = new FileCookieJar($this->config['service_layer']['cookies_storage_path'].'/'.$connection, true);
            $client = new Client(
                [
                    'cookies' => $jar,
                    'base_uri' => $this->config['service_layer']['base_uri'].$this->config['service_layer']['path'],
                    'verify' => $this->config['odata_service']['verify_https'],
                ]
            );
            $this->clients[$connection] = $client;
        }

        return $this;
    }

    /**
     * @param string $entityName
     *
     * @return AbstractRepository
     *
     * @throws \Exception
     */
    public function getRepository($entityName)
    {
        if (array_key_exists($entityName, $this->repositories)) {
            return $this->repositories[$entityName];
        }

        // checks if entity exists
        $entityClassName = $this->config['app_namespace'].'\\HanaEntity\\'.$entityName;
        if (!class_exists($entityClassName)) {
            throw new \Exception("Missing $entityName entity.");
        }

        if ('yaml' === $this->config['metadata_format']) {
            $metadata = $this->getYamlMetadata($entityClassName, $entityName);
        } else {
            $metadata = $this->getAnnotationMetadata($entityClassName, $entityName);
        }

        // checks if custom repo exists
        $repoClassName = $this->config['app_namespace'].'\\HanaRepository\\'.$entityName.'Repository';
        if (!class_exists($repoClassName)) {
            $repo = new DefaultRepository($metadata);
        } else {
            $repo = new $repoClassName($metadata);
            $this->logger->info("Loaded custom repo $repoClassName");
        }

        $this->repositories[$entityName] = $repo;

        return $repo;
    }

    /**
     * @param string $entityClassName
     * @param $entityName
     *
     * @return RepoMetadata
     *
     * @throws \ReflectionException
     * @throws \Exception
     */
    private function getAnnotationMetadata($entityClassName, $entityName)
    {
        $entityClass = new ReflectionClass($entityClassName);

        // reading EntityColumnMeta annotations
        $columns = [];
        $key = null;
        $attributes = $entityClass->getProperties();
        foreach ($attributes as $attribute) {
            if ($annotation = $this->reader->getPropertyAnnotation(
                $attribute,
                'W3com\\BoomBundle\\Annotation\\EntityColumnMeta'
            )) {
                $columns[$attribute->getName()] = [
                    'column' => $annotation->column,
                    'readColumn' => $annotation->readColumn,
                    'complexColumn' => $annotation->complexColumn,
                    'complexEntity' => $annotation->complexEntity,
                    'quotes' => $annotation->quotes,
                    'readOnly' => $annotation->readOnly,
                ];
                if ($annotation->isKey) {
                    $key = $attribute->getName();
                }
            }
        }
        if (null == $key) {
            throw new \Exception("No key attribute for $entityName class.");
        }

        // reading EntityMeta annotation
        $annotation = $this->reader->getClassAnnotation(
            $entityClass,
            'W3com\\BoomBundle\\Annotation\\EntityMeta'
        );
        if (!$annotation) {
            throw new \Exception("Missing EntityMeta annotation on $entityName class.");
        }
        $read = $annotation->read;
        $write = $annotation->write;
        $aliasRead = $annotation->aliasRead;
        $aliasWrite = $annotation->aliasWrite;

        $this->logger->info("Successfully read $entityName entity class");

        return new RepoMetadata(
            $entityName,
            $entityClassName,
            $this,
            $read,
            $write,
            $key,
            $aliasRead,
            $aliasWrite,
            $columns
        );
    }

    /**
     * @param $entityClassName
     * @param $entityName
     *
     * @return RepoMetadata
     *
     * @throws \ReflectionException
     */
    private function getYamlMetadata($entityClassName, $entityName)
    {
        return $this->getAnnotationMetadata($entityClassName, $entityName);
    }
}
