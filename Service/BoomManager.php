<?php


namespace W3com\BoomBundle\Service;


use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use ReflectionClass;
use Symfony\Component\Filesystem\Filesystem;
use W3com\BoomBundle\Repository\AbstractRepository;
use W3com\BoomBundle\Repository\DefaultRepository;
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
    private $clients = array();

    /**
     * @var array already loaded repositories
     */
    private $repositories = array();

    /**
     * @var array BOOM configuration
     */
    public $config;

    /**
     * @var array Rest clients (SL and ODS)
     */
    public $restClients = array();

    /**
     * BoomManager constructor.
     *
     * @param array $config
     */
    public function __construct($config)
    {
        $this->config = $config;

        // creating cookie jar if needed
        $fs = new Filesystem();
        $fs->mkdir($config['service_layer']['cookies_storage_path']);

        // creating the ODS client
        $jar = new FileCookieJar($config['service_layer']['cookies_storage_path'].'/odata');
        $client = new Client(
            array(
                'cookies' => $jar,
                'base_uri' => $config['odata_service']['base_uri'].'/'.$config['odata_service']['path'],
                'verify' => $config['odata_service']['verify_https'],
            )
        );
        $this->clients['odata'] = $client;

        // creating rest clients
        $slRestClient = new SLRestClient($this);
        $this->restClients['sl'] = $slRestClient;
        $odataRestClient = new OdataRestClient($this);
        $this->restClients['odata'] = $odataRestClient;
    }

    /**
     * @return string
     */
    public function getCurrentConnection()
    {
        return $this->currentConnection;
    }

    public function getCurrentClient()
    {
        return $this->clients[$this->currentConnection];
    }

    /**
     * @param $connection
     * @return BoomManager
     * @throws \Exception
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
            $jar = new FileCookieJar($this->config['service_layer']['cookies_storage_path'].'/'.$connection);
            $client = new Client(
                array(
                    'cookies' => $jar,
                    'base_uri' => $this->config['service_layer']['base_uri'].$this->config['service_layer']['path'],
                    'verify' => $this->config['odata_service']['verify_https'],
                )
            );
            $this->clients[$connection] = $client;
        }

        return $this;
    }

    /**
     * @param string $entityName
     * @return AbstractRepository
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
        $entityClass = new ReflectionClass($entityClassName);
        // TODO check if all constants exists

        // TODO getting the right repo
        $repo = new DefaultRepository(
            $entityName,
            $entityClassName,
            $this,
            $entityClass->getConstant('READ'),
            $entityClass->getConstant('WRITE'),
            $entityClass->getConstant('ALIAS_SL'),
            $entityClass->getConstant('ALIAS_ODS'),
            $entityClass->getConstant('KEY')
        );

        $this->repositories[$entityName] = $repo;

        return $repo;
    }


}