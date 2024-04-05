<?php

namespace W3com\BoomBundle\Utils;

class RawRequest
{
    private string $request;

    private string $response;

    private array $results;

    private array $flattenedResults = [];

    public function __construct(string $request)
    {
        $this->setRequest($request);
    }

    public function handleResponse($response)
    {
        $this->response = $response ?? '';
        $decoded=json_decode($response, true) ?? [];
        if (array_key_exists('odata.metadata', $decoded)){
            if (array_key_exists('odata.etag', $decoded)){
                $this->results=$decoded;
            } else {
                if (!array_key_exists('value', $decoded)){
                    throw new \Exception('Error RawRequest : invalid response.');
                }
                $this->results = $decoded['value'];
            }

        } else {
            if ($decoded !== []) {
                throw new \Exception('Error RawRequest : invalid response.');
            }
            $this->results = $decoded;
        }
    }

    public function setRequest(string $request)
    {
        $this->request=$request;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function getResponse(): string
    {
        return $this->response;
    }

    public function getFlattenedResults(): array
    {
        if ($this->flattenedResults === []){
            foreach ($this->results as $result){
                $line = [];
                foreach($result as $table=>$data){
                    $line = array_merge($line,$data);
                }
                $this->flattenedResults[] = $line;
            }
        }
        return $this->flattenedResults;
    }
}