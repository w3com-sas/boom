<?php


namespace W3com\BoomBundle\RestClient;


use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use W3com\BoomBundle\Service\BoomManager;

class OdataRestClient implements RestClientInterface
{
    /**
     * @var BoomManager
     */
    private $manager;

    private $client;

    private $auth;

    public function __construct(BoomManager $manager)
    {
        $this->manager = $manager;
        $this->client = $manager->getOdsClient();
        $this->auth = array(
            $this->manager->config['odata_service']['login']['username'],// login
            $this->manager->config['odata_service']['login']['password']// password
        );
    }

    public function get(string $uri)
    {
        try {
            $this->manager->stopwatch->start('ODS-get');
            $res = $this->client->request('GET', $uri, array('auth' => $this->auth));
            $response = $res->getBody()->getContents();
            $stop = $this->manager->stopwatch->stop('ODS-get');
            $this->manager->addToCollectedData('ods', $res->getStatusCode(), $uri, $response, $stop);
            return $this->getValuesFromResponse($response);
        } catch (ClientException $e) {
            $stop = $this->manager->stopwatch->stop('ODS-get');
            $response = $e->getResponse()->getBody()->getContents();
            $this->manager->addToCollectedData('ods', $e->getResponse()->getStatusCode(), $uri, $response, $stop);
            if ($e->getCode() == 404) {
                $this->manager->logger->info($e->getResponse()->getBody()->getContents());

                return null;
            } else {
                $this->manager->logger->error($e->getResponse()->getBody()->getContents());
                throw new \Exception("Unknown error while launching GET request");
            }
        } catch (ConnectException $e) {
            $this->manager->stopwatch->stop('ODS-get');
            $this->manager->logger->error($e->getMessage());
            throw new \Exception("Connection error, check if config is OK, or maybe some needed VPN in on.");
        }
    }

    public function post(string $uri, $data)
    {
        // TODO: Implement post() method.
    }

    public function patch(string $uri, $data)
    {
        // TODO: Implement patch() method.
    }

    public function put(string $uri, $data)
    {
        // TODO: Implement put() method.
    }

    public function delete(string $uri)
    {
        // TODO: Implement delete() method.
    }

    public function getValuesFromResponse($response)
    {
        $ar = json_decode($response, true);
        if (json_last_error() != 0) {
            $this->manager->logger->error(substr(0, 255, $response));
            throw new \Exception("Error while parsing response");
        }
        if (is_int($ar)) {
            return $ar;
        } // we just counted something
        if (array_key_exists('d', $ar)) {
            if (array_key_exists('results', $ar['d'])) {
                return $ar['d']['results'];
            } else {
                return $ar['d'];
            }
        }

        return $ar;
    }
}