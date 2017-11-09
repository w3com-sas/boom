<?php


namespace W3com\BoomBundle\RestClient;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use W3com\BoomBundle\Service\BoomManager;

class SLRestClient implements RestClientInterface
{
    /**
     * @var BoomManager
     */
    private $manager;

    public function __construct(BoomManager $manager)
    {
        $this->manager = $manager;
    }

    public function get(string $uri)
    {
        /** @var Client $client */
        $client = $this->manager->getCurrentClient();
        $attempts = 0;
        while ($attempts < $this->manager->config['service_layer']['max_login_attempts']) {
            try {
                $attempts++;
                $res = $client->request('GET', $uri);
                $response = $res->getBody()->getContents();
                $this->manager->addToCollectedData('sl', $res->getStatusCode(), $uri, $response);

                return $this->getValuesFromResponse($response);
            } catch (ClientException $e) {
                if ($e->getCode() == 401) {
                    $this->login();
                } else {
                    $this->manager->logger->error($e->getResponse()->getBody()->getContents());
                    throw new \Exception("Unknown error while launching GET request");
                }
            } catch (ConnectException $e) {
                $this->manager->logger->error($e->getMessage());
                throw new \Exception("Connection error, check if config is OK, or maybe some needed VPN in on.");
            }
        }


    }

    public function post(string $uri, $data)
    {
        /** @var Client $client */
        $client = $this->manager->getCurrentClient();
        $attempts = 0;
        while ($attempts < $this->manager->config['service_layer']['max_login_attempts']) {
            try {
                $attempts++;
                $res = $client->request(
                    'POST',
                    $uri,
                    array(
                        'json' => $data,
                    )
                );
                $response = $res->getBody()->getContents();
                $this->manager->addToCollectedData('sl', $res->getStatusCode(), $uri, $response);

                return $this->getValuesFromResponse($response);
            } catch (ClientException $e) {
                if ($e->getCode() == 401) {
                    $this->login();
                } else {
                    $this->manager->logger->error($e->getResponse()->getBody()->getContents());
                    throw new \Exception("Unknown error while launching POST request");
                }
            } catch (ConnectException $e) {
                $this->manager->logger->error($e->getMessage());
                throw new \Exception("Connection error, check if config is OK, or maybe some needed VPN in on.");
            }
        }
    }

    public function patch(string $uri, $data)
    {
        /** @var Client $client */
        $client = $this->manager->getCurrentClient();
        $attempts = 0;
        while ($attempts < $this->manager->config['service_layer']['max_login_attempts']) {
            try {
                $attempts++;
                $res = $client->request(
                    'PATCH',
                    $uri,
                    array(
                        'json' => $data,
                    )
                );
                $this->manager->addToCollectedData('sl', $res->getStatusCode(), $uri, $res->getBody()->getContents());
                return true;
            } catch (ClientException $e) {
                if ($e->getCode() == 401) {
                    $this->login();
                } else {
                    $this->manager->logger->error($e->getResponse()->getBody()->getContents());
                    throw new \Exception("Unknown error while launching PATCH request");
                }
            } catch (ConnectException $e) {
                $this->manager->logger->error($e->getMessage());
                throw new \Exception("Connection error, check if config is OK, or maybe some needed VPN in on.");
            }
        }
    }

    public function put(string $uri, $data)
    {
        // TODO: Implement put() method.
    }

    public function delete(string $uri)
    {
        /** @var Client $client */
        $client = $this->manager->getCurrentClient();
        $attempts = 0;
        while ($attempts < $this->manager->config['service_layer']['max_login_attempts']) {
            try {
                $attempts++;
                $res = $client->request('DELETE', $uri);
                $response = $res->getBody()->getContents();
                $this->manager->addToCollectedData('sl', $res->getStatusCode(), $uri, $response);

                return $this->getValuesFromResponse($response);
            } catch (ClientException $e) {
                if ($e->getCode() == 401) {
                    $this->login();
                } else {
                    $this->manager->logger->error($e->getResponse()->getBody()->getContents());
                    throw new \Exception("Unknown error while launching DELETE request");
                }
            } catch (ConnectException $e) {
                $this->manager->logger->error($e->getMessage());
                throw new \Exception("Connection error, check if config is OK, or maybe some needed VPN in on.");
            }
        }
    }

    public function getValuesFromResponse($response)
    {
        $ar = json_decode($response, true);
        if (json_last_error() != 0) {
            $this->manager->logger->error(substr($response, 0, 255));
            throw new \Exception("Error while parsing response");
        }
        if (is_int($ar)) {
            return $ar;
        } // we just counted something
        if (array_key_exists('value', $ar)) {
            return $ar['value'];
        }

        return $ar;
    }

    private function login()
    {
        try {
            $loginData = $this->manager->config['service_layer']['connections'][$this->manager->getCurrentConnection()];
            $res = $this->manager->getCurrentClient()->post(
                'Login',
                array(
                    'json' => array(
                        'UserName' => $loginData['username'],
                        'Password' => $loginData['password'],
                        'CompanyDB' => $loginData['database'],
                    ),
                )
            );
            $this->manager->addToCollectedData('sl', $res->getStatusCode(), 'Login', $res->getBody()->getContents());
            if ($res->getStatusCode() == 200) {
                // on est loggués
                $this->manager->logger->info('Successfully loggued as '.$loginData['username'].'.');
            } else {
                // le log a planté :(
                $this->manager->logger->info('Loging as '.$loginData['username'].' failed.');
            }
        } catch (ClientException $e) {
            $this->manager->logger->error($e->getResponse()->getBody()->getContents());
            throw new \Exception("Unknown error while loging in");
        }
    }
}