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
                $this->manager->stopwatch->start('SL-get');
                $res = $client->request('GET', $uri);
                $stop = $this->manager->stopwatch->stop('SL-get');
                $response = $res->getBody()->getContents();
                $this->manager->addToCollectedData('sl', $res->getStatusCode(), $uri, null, $response, $stop);

                return $this->getValuesFromResponse($response);
            } catch (ClientException $e) {
                if ($e->getCode() == 401) {
                    $this->login();
                } else {
                    $stop = $this->manager->stopwatch->stop('SL-get');
                    $response = $e->getResponse()->getBody()->getContents();
                    $this->manager->addToCollectedData(
                        'sl',
                        $e->getResponse()->getStatusCode(),
                        $uri,
                        null,
                        $response,
                        $stop
                    );
                    if ($e->getCode() == 404) {
                        $this->manager->logger->info($response);

                        return null;
                    } else {
                        $this->manager->logger->error($response);
                        throw new \Exception("Unknown error while launching GET request");
                    }
                }
            } catch (ConnectException $e) {
                $this->manager->stopwatch->stop('SL-get');
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
                $this->manager->stopwatch->start('SL-post');
                $res = $client->request(
                    'POST',
                    $uri,
                    array(
                        'json' => $data,
                    )
                );
                $stop = $this->manager->stopwatch->stop('SL-post');
                $response = $res->getBody()->getContents();
                $this->manager->addToCollectedData('sl', $res->getStatusCode(), $uri, $data, $response, $stop);

                return $this->getValuesFromResponse($response);
            } catch (ClientException $e) {
                if ($e->getCode() == 401) {
                    $this->login();
                } else {
                    $stop = $this->manager->stopwatch->stop('SL-post');
                    $response = $e->getResponse()->getBody()->getContents();
                    $this->manager->addToCollectedData(
                        'sl',
                        $e->getResponse()->getStatusCode(),
                        $uri,
                        $data,
                        $response,
                        $stop
                    );
                    $this->manager->logger->error($response);
                    throw new \Exception("Unknown error while launching POST request");
                }
            } catch (ConnectException $e) {
                $this->manager->stopwatch->stop('SL-post');
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
                $this->manager->stopwatch->start('SL-patch');
                $res = $client->request(
                    'PATCH',
                    $uri,
                    array(
                        'json' => $data,
                    )
                );
                $stop = $this->manager->stopwatch->stop('SL-patch');
                $this->manager->addToCollectedData(
                    'sl',
                    $res->getStatusCode(),
                    $uri,
                    $data,
                    $res->getBody()->getContents(),
                    $stop
                );
                return true;
            } catch (ClientException $e) {
                if ($e->getCode() == 401) {
                    $this->login();
                } else {
                    $stop = $this->manager->stopwatch->stop('SL-patch');
                    $response = $e->getResponse()->getBody()->getContents();
                    $this->manager->addToCollectedData(
                        'sl',
                        $e->getResponse()->getStatusCode(),
                        $uri,
                        $data,
                        $response,
                        $stop
                    );
                    $this->manager->logger->error($response);
                    throw new \Exception("Unknown error while launching PATCH request");
                }
            } catch (ConnectException $e) {
                $this->manager->stopwatch->stop('SL-patch');
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
                $this->manager->stopwatch->start('SL-delete');
                $res = $client->request('DELETE', $uri);
                $stop = $this->manager->stopwatch->stop('SL-delete');
                $response = $res->getBody()->getContents();
                $this->manager->addToCollectedData('sl', $res->getStatusCode(), $uri, null, $response, $stop);

                $ret = ($response == '') ? true : $this->getValuesFromResponse($response);

                return $ret;
            } catch (ClientException $e) {
                if ($e->getCode() == 401) {
                    $this->login();
                } else {
                    $stop = $this->manager->stopwatch->stop('SL-delete');
                    $response = $e->getResponse()->getBody()->getContents();
                    $this->manager->addToCollectedData(
                        'sl',
                        $e->getResponse()->getStatusCode(),
                        $uri,
                        null,
                        $response,
                        $stop
                    );
                    $this->manager->logger->error($response);
                    throw new \Exception("Unknown error while launching DELETE request");
                }
            } catch (ConnectException $e) {
                $this->manager->stopwatch->stop('SL-delete');
                $this->manager->logger->error($e->getMessage());
                throw new \Exception("Connection error, check if config is OK, or maybe some needed VPN in on.");
            }
        }
    }

    public function getValuesFromResponse($response)
    {
        $ar = json_decode($response, true);
        if (json_last_error() != 0) {
            $this->manager->logger->error("Error while parsing response in SL : ".substr($response, 0, 255));
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
        $loginData = $this->manager->config['service_layer']['connections'][$this->manager->getCurrentConnection()];
        $collectedData = $loginData;
        unset($collectedData['password']);
        try {

            $this->manager->stopwatch->start('SL-login');
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
            $stop = $this->manager->stopwatch->stop('SL-login');
            $this->manager->addToCollectedData(
                'sl',
                $res->getStatusCode(),
                'Login',
                $collectedData,
                $res->getBody()->getContents(),
                $stop
            );
            if ($res->getStatusCode() == 200) {
                // on est loggués
                $this->manager->logger->info('Successfully loggued as '.$loginData['username'].'.');
            } else {
                // le log a planté :(
                $this->manager->logger->info('Loging as '.$loginData['username'].' failed.');
            }
        } catch (ClientException $e) {
            $stop = $this->manager->stopwatch->stop('SL-login');
            $response = $e->getResponse()->getBody()->getContents();
            $this->manager->addToCollectedData(
                'sl',
                $e->getResponse()->getStatusCode(),
                'Login',
                $collectedData,
                $response,
                $stop
            );
            $this->manager->logger->error($response);
            throw new \Exception("Unknown error while loging in");
        }
    }
}