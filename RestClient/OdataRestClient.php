<?php


namespace W3com\BoomBundle\RestClient;


use GuzzleHttp\Exception\ClientException;
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
            $res = $this->client->request('GET', $uri, array('auth' => $this->auth));

            return $this->getValuesFromResponse($res->getBody()->getContents());
        } catch (ClientException $e) {
            dump($e->getResponse()->getBody()->getContents());
            throw new \Exception("Unknown error while launching GET request");
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