<?php


namespace W3com\BoomBundle\RestClient;


use W3com\BoomBundle\Service\BoomManager;

class OdataRestClient implements RestClientInterface
{
    /**
     * @var BoomManager
     */
    private $manager;

    public function get(string $uri)
    {
        // TODO: Implement get() method.
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
        // TODO: Implement getValuesFromResponse() method.
    }

    public function __construct(BoomManager $manager)
    {
        $this->manager = $manager;
    }
}