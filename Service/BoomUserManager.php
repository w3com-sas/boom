<?php

namespace W3com\BoomBundle\Service;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Filesystem\Filesystem;

class BoomUserManager
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var array
     */
    private $config;

    /**
     * @var BoomManager
     */
    private $boom;

    /**
     * @var string
     */
    private $credentialsPath;

    /**
     * @var array
     */
    private $clients;

    public function __construct($config, BoomManager $boom, array $clients)
    {
        $this->config = $config;
        $this->boom = $boom;
        $this->clients = $clients;
        $this->filesystem = new Filesystem();
        $this->credentialsPath = $config['service_layer']['cookies_storage_path'] . '/CREDENTIALS_';
    }

    /**
     * @return array|bool[]
     * @throws Exception
     */
    public function loginForUser(string $username, string $password, string $companyDb): array
    {
        try {
            $client = $this->buildUserClient($username, $password, $companyDb);

            $client->post('Login',
                [
                    'json' => [
                        'UserName' => $username,
                        'Password' => $password,
                        'CompanyDB' => $companyDb,
                        'Language' => "22"
                    ]
                ]
            );

            $this->saveCredentials($username, $password, $companyDb);
            $this->boom->setCurrentConnection(
                $this->getConnectionId($username, $companyDb)
            );

            return ['valid' => true];
        } catch (ClientException $e) {
            return ['valid' => false, 'message' => $e];
        }
    }

    /**
     * @throws Exception
     */
    public function setConnectedUser(string $username, string $companyDb, bool $useDefault = true): bool
    {
        $connected = false;
        $credentials = $this->getCredentials($username, $companyDb);

        if (!empty($credentials)) {
            $this->buildUserClient($credentials['username'], $credentials['password'], $credentials['company_db']);
            $connected = true;
        }

        if ($useDefault) {
            $this->boom->setCurrentConnection('default');
            $connected = true;
        }

        return $connected;
    }

    public function getConnectionId(string $username, string $companyDb): string
    {
        return $companyDb . '_' . $username;
    }

    private function buildUserClient(string $username, string $password, string $companyDb): Client
    {
        $key = $this->getConnectionId($username, $companyDb);
        $cookiePath = $this->config['service_layer']['cookies_storage_path'] . '/' . 'USER_' . $key;
        $defaultConnection = $this->config['service_layer']['connections']['default'];
        $jar = new FileCookieJar($cookiePath, true);

        $client = new Client([
            'cookies' => $jar,
            'base_uri' => $defaultConnection['uri'] . $defaultConnection['path'],
            'verify' => $this->config['service_layer']['verify_https'],
        ]);

        $this->clients[$key] = $client;

        $this->config['service_layer']['connections'][$key] = [
            'uri' => $defaultConnection['uri'],
            'path' => $defaultConnection['path'],
            'username' => $username,
            'password' => $password,
            'database' => $companyDb
        ];

        $this->boom
            ->setClients($this->clients)
            ->setConfig($this->config);

        return $client;
    }

    /**
     * @return array|mixed
     */
    private function getCredentials(string $username, string $companyDb)
    {
        if ($this->filesystem->exists($this->credentialsPath . $username . '_' . $companyDb)) {
            return json_decode(
                file_get_contents($this->credentialsPath . $username . '_' . $companyDb),
                true
            );
        }

        return [];
    }

    private function saveCredentials(string $username, string $password, string $companyDb)
    {
        $this->filesystem->dumpFile(
            $this->credentialsPath . $username . '_' . $companyDb,
            json_encode([
                'username' => $username,
                'password' => $password,
                'company_db' => $companyDb
            ])
        );
    }
}
