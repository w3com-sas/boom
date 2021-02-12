<?php

namespace W3com\BoomBundle\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Filesystem\Filesystem;

class BoomUserManager
{
    /** @var Filesystem */
    private $filesystem;

    /** @var array */
    private $config;

    /** @var BoomManager */
    private $manager;

    /** @var string $credentialsPath */
    private $credentialsPath;

    /** @var array $clients */
    private $clients;

    public function __construct($config, BoomManager $boom, array $clients)
    {
        $this->config = $config;
        $this->manager = $boom;
        $this->clients = $clients;
        $this->filesystem = new Filesystem();
        $this->credentialsPath = $config['service_layer']['cookies_storage_path'] . '/CREDENTIALS_';
    }

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
                    ]
                ]
            );
            $this->saveCredentials($username, $password, $companyDb);
            return ['valid' => true];
        } catch (ClientException $e) {
            return ['valid' => false, 'message' => $e];
        }
    }

    public function setConnectedUser(string $username, string $companyDb)
    {
        $credentials = $this->getCredentials($username, $companyDb);
        $this->buildUserClient($credentials['username'], $credentials['password'], $companyDb['company_db']);
    }

    private function buildUserClient(string $username, string $password, string $companyDb): Client
    {
        $key = $companyDb . '_' . $username;
        $cookiePath = $this->config['service_layer']['cookies_storage_path'] . '/' . 'USER_' . $key;
        $defaultConnection = $this->config['service_layer']['connections']['default'];

        $jar = new FileCookieJar($cookiePath, true);
        $client = new Client(
            [
                'cookies' => $jar,
                'base_uri' => $defaultConnection['uri'] . $defaultConnection['path'],
                'verify' => $this->config['service_layer']['verify_https'],
            ]
        );
        $this->clients[$key] = $client;

        $this->config['service_layer']['connections'][$key] = [
            "uri" => $defaultConnection["uri"],
            "path" => $defaultConnection["path"],
            "username" => $username,
            "password" => $password,
            "database" => $companyDb
        ];

        return $client;
    }

    private function getCredentials(string $username, string $companyDb): ?array
    {
        if ($this->filesystem->exists($this->credentialsPath . $username . '_' . $companyDb)) {
            return json_decode(
                file_get_contents($this->credentialsPath . $username . '_' . $companyDb)
            );
        }
        return null;
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