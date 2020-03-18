<?php

namespace W3com\BoomBundle\Service;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use ReflectionClass;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;
use W3com\BoomBundle\Exception\EntityNotFoundException;
use W3com\BoomBundle\HanaEntity\AbstractEntity;
use W3com\BoomBundle\Http\Batch;
use W3com\BoomBundle\Repository\AbstractRepository;
use W3com\BoomBundle\Repository\DefaultRepository;
use W3com\BoomBundle\Repository\RepoMetadata;
use W3com\BoomBundle\RestClient\OdataRestClient;
use W3com\BoomBundle\RestClient\SLRestClient;
use W3com\BoomBundle\Utils\StringUtils;

class BoomManager
{
    const BATCH_CREATE = 'CREATE';
    const BATCH_UPDATE = 'UPDATE';

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
     * @var bool
     */
    private $inSlContextMode = false;

    /**
     * @var Batch
     */
    private $batch;

    /**
     * @var string
     */
    private $last_cookie_file_path = '';

    /**
     * BoomManager constructor.
     *
     * @param array $config
     * @param Logger $logger
     * @param Stopwatch|null $stopwatch
     * @param AdapterInterface $cache
     * @param RequestStack $request
     * @throws AnnotationException
     */
    public function __construct($config, Logger $logger, Stopwatch $stopwatch, AdapterInterface $cache, RequestStack $request)
    {
        if ($request->getCurrentRequest() != null) {
            $query = $request->getCurrentRequest()->query;
            $session = $request->getCurrentRequest()->getSession();

            if ($query->has('slcontext') && $query->has('username') && $query->has('companydb')) {
                $session->set('username', $query->get('username'));
                $session->set('companydb', $query->get('companydb'));
                $session->set('slcontext', $query->get('slcontext'));
            }
            if ($session->has('username') && $session->has('companydb') && $session->has('slcontext')) {
                $this->inSlContextMode = true;
            }
        } else {
            $session = null;
        }
        $this->config = $config;
        $this->reader = new AnnotationReader();
        $this->logger = $logger;
        $this->stopwatch = $stopwatch;

        // creating cookie jar if needed
        $fs = new Filesystem();
        $fs->mkdir($config['service_layer']['cookies_storage_path']);

        // creating the ODS client
        $jar = new FileCookieJar($config['service_layer']['cookies_storage_path'] . '/odata', true);
        $client = new Client(
            [
                'cookies' => $jar,
                'base_uri' => $config['odata_service']['base_uri'] . '/' . $config['odata_service']['path'],
                'verify' => $config['odata_service']['verify_https'],
            ]
        );
        $this->clients['odata'] = $client;

        if ($this->inSlContextMode) {
            $this->setSLContextToCurrentConnection(
                $session->get('slcontext'),
                $session->get('username'),
                $session->get('companydb'));
        } else {
            // creating the default SL connection
            $this->setCurrentConnection('default');
        }

        // creating rest clients
        $slRestClient = new SLRestClient($this, $cache);
        $this->restClients['sl'] = $slRestClient;
        $odataRestClient = new OdataRestClient($this, $cache);
        $this->restClients['odata'] = $odataRestClient;
        $this->batch = new Batch($this->getCurrentClient(), $config, $stopwatch);
    }

    public function getCollectedData()
    {
        return $this->collectedData;
    }

    public function reloginToSL()
    {
        $loginData = $this->config['service_layer']['connections'][$this->getCurrentConnection()];

        try {
            $res = $this->getCurrentClient()->post(
                'Login',
                [
                    'json' => [
                        'UserName' => $loginData['username'],
                        'Password' => $loginData['password'],
                        'CompanyDB' => $loginData['database'],
                    ],
                ]
            );

            return [
                'valid' => true,
                'data' => $res
            ];
        } catch (ClientException $e) {
            return [
                'valid' => false,
                'data' => $e
            ];
        }

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
        $decoded = !is_array($response) ? json_decode($response) : $response;
        if (is_object($decoded) ||is_array($response)) {
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
     * @param $SLContext
     * @param $UserName
     * @param $CompanyDB
     */
    public function setSLContextToCurrentConnection($SLContext, $UserName, $CompanyDB)
    {
        $connection = 'SLContext';

        $this->currentConnection = $connection;

        if (!array_key_exists($connection, $this->clients)) {
            // creating the cookie jar
            $cookiePath = $this->config['service_layer']['cookies_storage_path'] . '/' .
                $connection . '_' . $CompanyDB . '_' . $UserName;
            file_put_contents($cookiePath, StringUtils::convertSLContextToCookieJarFileContent($SLContext, $this->config['service_layer']['base_uri']));
            $jar = new FileCookieJar($cookiePath, true);

            $client = new Client(
                [
                    'cookies' => $jar,
                    'base_uri' => $this->config['service_layer']['base_uri'] . $this->config['service_layer']['path'],
                    'verify' => $this->config['odata_service']['verify_https'],
                ]
            );

            $this->clients[$connection] = $client;

        }
    }

    /**
     * @return Client
     */
    public function getOdsClient()
    {
        return $this->clients['odata'];
    }

    /**
     * @return Client
     */
    public function getSlClient()
    {
        return $this->restClients['sl'];
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
            $this->last_cookie_file_path = $this->config['service_layer']['cookies_storage_path'] . '/' .
                $connection . '_' . $this->config['service_layer']['connections'][$connection]['database'] . '_' .
                str_replace('\\', '_', $this->config['service_layer']['connections'][$connection]['username']);
            $jar = new FileCookieJar($this->last_cookie_file_path,true);

            $client = new Client(
                [
                    'cookies' => $jar,
                    'base_uri' => $this->config['service_layer']['base_uri'] . $this->config['service_layer']['path'],
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
        $boomEntityClassName = 'W3com\\BoomBundle\\HanaEntity\\' . $entityName;

        if (class_exists($boomEntityClassName)) {
            $entityClassName = $boomEntityClassName;
        } else {
            $entityClassName = $this->config['app_namespace'] . '\\HanaEntity\\' . $entityName;
            if (!class_exists($entityClassName) && !class_exists($boomEntityClassName)) {
                throw new EntityNotFoundException($entityClassName);
            }
        }

        if ('yaml' === $this->config['metadata_format']) {
            $metadata = $this->getYamlMetadata($entityClassName, $entityName);
        } else {
            $metadata = $this->getAnnotationMetadata($entityClassName, $entityName);
        }

        // checks if custom repo exists
        $repoClassName = $this->config['app_namespace'] . '\\HanaRepository\\' . $entityName . 'Repository';
        if (!class_exists($repoClassName)) {
            $repoClassName = 'W3com\\BoomBundle\\HanaRepository\\' . $entityName . 'Repository';
            if (class_exists($repoClassName)) {
                $repo = new $repoClassName($metadata);
            } else {
                $repo = new DefaultRepository($metadata);
            }
        } else {
            $repo = new $repoClassName($metadata);
            $this->logger->info("Loaded custom repo $repoClassName");
        }
        $this->repositories[$entityName] = $repo;
        return $repo;
    }

    public function removeLastCookieFile()
    {
        if($this->last_cookie_file_path != ''){
            unlink($this->last_cookie_file_path);
        }
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
                    'ipName' => $annotation->ipName,
                ];
                if ($annotation->isKey) {
                    $key = $attribute->getName();
                }
            }
        }

        // reading EntityMeta annotation
        $annotation = $this->reader->getClassAnnotation(
            $entityClass,
            'W3com\\BoomBundle\\Annotation\\EntityMeta'
        );

        if (null == $key && !$annotation->isComplex) {
            throw new \Exception("No key attribute for $entityName class.");
        }


        $read = !$annotation ? "" : $annotation->read;
        $write = !$annotation ? "" : $annotation->write;
        $aliasRead = !$annotation ? "" : $annotation->aliasRead;
        $aliasWrite = !$annotation ? "" : $annotation->aliasWrite;
        $aliasSearch = !$annotation ? "" : $annotation->aliasSearch;

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
            $aliasSearch,
            $columns
        );
    }

    /**
     * @param $entityClassName
     * @param $entityName
     * @return RepoMetadata
     * @throws \ReflectionException
     */
    private function getYamlMetadata($entityClassName, $entityName)
    {
        return $this->getAnnotationMetadata($entityClassName, $entityName);
    }

    public function sendAttachment($documents, $absoluteEntry = 0)
    {
        $isNew = intval($absoluteEntry) == 0;
        $client = $this->restClients['sl'];
        $customBoundary = md5(time());
        $rawBody = "";


        foreach ($documents as $document) {

            if (!array_key_exists('path', $document)) continue;
            if (!file_exists($document['path'])) continue;

            $fileUtil = new File($document['path']);
            $systemFilename = $fileUtil->getFilename();
            $filetype = $fileUtil->getMimeType();

            if (array_key_exists('filename', $document)) {
                $serverFilename = $document['filename'];
            } else {
                $serverFilename = $systemFilename;
            }

            $rawBody .=
                "--$customBoundary\r\n"
                . "Content-Disposition: form-data; name=\"files\"; filename=\"" . $serverFilename . "\"\r\n"
                . "Content-Type: " . $filetype . "\r\n"
                . "\r\n"
                . file_get_contents($document['path']) . "\r\n";
        }

        $rawBody .= "--$customBoundary--\r\n\r\n";

        if ($isNew) {
            $response = $client->request(
                'Attachments2',
                [
                    'headers' => [
                        'Content-Type' => 'multipart/form-data;boundary=' . $customBoundary,
                    ],
                    'body' => $rawBody
                ]
            );
            $absoluteEntry = $response['AbsoluteEntry'];
        } else {
            $response = $client->request(
                'Attachments2(' . $absoluteEntry . ')',
                [
                    'headers' => [
                        'Content-Type' => 'multipart/form-data;boundary=' . $customBoundary,
                    ],
                    'body' => $rawBody
                ]
            );
        }
        return $absoluteEntry;
    }


    public function getAttachmentInfos($absEntry)
    {
        $response = $this->restClients['sl']->get('Attachments2(' . $absEntry . ')');
        return $response['Attachments2_Lines'];
    }

    public function downloadAttachment($absEntry, $fileName = null)
    {
        if ($fileName == null) {
            return $this->restClients['sl']->get('Attachments2(' . $absEntry . ')/$value', true);
        } else {
            return $this->restClients['sl']->get('Attachments2(' . $absEntry . ')/$value?filename=\'' . $fileName . '\'', true);
        }
    }

    /**
     * @param AbstractEntity $entity
     * @throws \Exception
     */
    public function update(AbstractEntity $entity)
    {
        $data = $this->getDataFromEntity($entity, true);
        $this->batch->add(new Request('PATCH', $data['uri'], ['Content-Type' => 'application/json'],
            json_encode($data['data'])));
    }

    /**
     * @param AbstractEntity $entity
     * @throws \Exception
     */
    public function delete(AbstractEntity $entity)
    {
        $data = $this->getDataFromEntity($entity, true);
        $this->batch->add(new Request('DELETE', $data['uri'], ['Content-Type' => 'application/json'],
            json_encode($data['data'])));
    }

    /**
     * @param AbstractEntity $entity
     * @throws \Exception
     */
    public function add(AbstractEntity $entity)
    {
        $data = $this->getDataFromEntity($entity);
        $this->batch->add(new Request('POST', $data['uri'], ['Content-Type' => 'application/json'],
            json_encode($data['data'])));
    }

    public function cancel(AbstractEntity $entity)
    {
        $data = $this->getDataFromEntity($entity,true);
        $this->batch->add(new Request('POST', $data['uri'].'/Cancel', ['Content-Type' => 'application/json'],
            null));
    }

    /**
     * @throws \Exception
     * @throws GuzzleException
     */
    public function flush()
    {
        try {
            $this->stopwatch->start('SL-batch');
            $response = $this->batch->execute();
            $stop = $this->stopwatch->stop('SL-batch');
            $this->addToCollectedData('SL-BATCH', $response->getStatusCode(),
                '$batch', null, (string)$response->getBody()->getContents(), $stop);
        } catch (ClientException $exception){
            if ($exception->getCode() === 401){
                $this->getSlClient()->login();
                return $this->flush();
            }
        }
    }

    /**
     * @param AbstractEntity $entity
     * @param bool $objectExist
     * @return array
     * @throws \Exception
     */
    private function getDataFromEntity(AbstractEntity $entity, $objectExist = false)
    {
        $class = substr(get_class($entity), strrpos(get_class($entity), '\\') + 1);
        $repo = $this->getRepository($class);
        $repoMetadata = $repo->getRepoMetadata();
        $uri = $repoMetadata->getAliasWrite();
        if ($objectExist) {
            $id = $entity->get($repoMetadata->getKey());
            $quotes = $repoMetadata->getColumns()[$repoMetadata->getKey()]['quotes'] ? "'" : "";
            $uri .= '(' . $quotes . $id . $quotes . ')';
        }
        $data = $repo->getDataToSend($entity->getChangedFields(), $entity);
        return ['data' => $data, 'uri' => $uri];
    }

}
