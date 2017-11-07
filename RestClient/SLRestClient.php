<?php


namespace W3com\BoomBundle\RestClient;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use W3com\BoomBundle\Service\BoomManager;

class SLRestClient implements RestClientInterface
{
    /**
     * @var BoomManager
     */
    private $manager;

    public function get(string $uri)
    {
        /** @var Client $client */
        $client = $this->manager->getCurrentClient();
        $attempts = 0;
        while ($attempts < $this->manager->config['service_layer']['max_login_attempts']) {
            try {
                $attempts++;
                $res = $client->request('GET', $uri);

                return $this->getValuesFromResponse($res->getBody()->getContents());
            } catch (ClientException $e) {
                if ($e->getCode() == 401) {
                    $this->login();
                } else {
                    dump($e->getResponse()->getBody()->getContents());
                    throw new \Exception("Unknown error while launching GET request");
                }
            }
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
            if ($res->getStatusCode() == 200) {
                // on est loggués
            } else {
                // le log a planté :(
            }
        } catch (ClientException $e) {
            dump($e->getResponse()->getBody()->getContents());
        }
    }

    public function __construct(BoomManager $manager)
    {
        $this->manager = $manager;
    }
}