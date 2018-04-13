<?php

namespace W3com\BoomBundle\RestClient;

use W3com\BoomBundle\Service\BoomManager;

interface RestClientInterface
{
    public function get(string $uri);

    public function post(string $uri, $data);

    public function patch(string $uri, $data);

    public function put(string $uri, $data);

    public function delete(string $uri);

    public function getValuesFromResponse($response);

    public function __construct(BoomManager $manager);
}
