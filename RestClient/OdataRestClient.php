<?php

namespace W3com\BoomBundle\RestClient;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use W3com\BoomBundle\Service\BoomManager;

class OdataRestClient implements RestClientInterface
{

    const ODS_METADATA_URI = '$metadata';
    /**
     * @var BoomManager
     */
    private $manager;

    private $client;

    private $auth;

    private $xmlEncoder;

    public function __construct(BoomManager $manager)
    {
        $this->manager = $manager;
        $this->client = $manager->getOdsClient();
        $this->auth = [
            $this->manager->config['odata_service']['login']['username'], // login
            $this->manager->config['odata_service']['login']['password'], // password
        ];
        $this->xmlEncoder = new XmlEncoder();
    }

    public function get(string $uri)
    {
        try {
            $this->manager->stopwatch->start('ODS-get');

            // For the Hosted by SAP the parameters PageSize in b1s.conf is not accessible
            // so we deactivate the pagination by pass the Prefer param in the header
            $param = [
                'auth' => $this->auth,
                'headers' => [
                    'Prefer' => 'odata.maxpagesize=100000'
                ]
            ];

            //$res = $this->client->request('GET', $uri, ['auth' => $this->auth]);
            $res = $this->client->request('GET', $uri, $param);

            $response = $res->getBody()->getContents();
            $stop = $this->manager->stopwatch->stop('ODS-get');
            $this->manager->addToCollectedData('ods', $res->getStatusCode(), $uri, null, $response, $stop);

            return $this->getValuesFromResponse($response);
        } catch (ClientException $e) {
            $stop = $this->manager->stopwatch->stop('ODS-get');
            $response = $e->getResponse()->getBody()->getContents();
            $this->manager->addToCollectedData('ods', $e->getResponse()->getStatusCode(), $uri, null, $response, $stop);
            if (404 == $e->getCode()) {
                $this->manager->logger->info($e->getResponse()->getBody()->getContents());

                return null;
            } else {
                $this->manager->logger->error($e->getResponse()->getBody()->getContents());
                throw new \Exception('Unknown error while launching GET request');
            }
        } catch (ConnectException $e) {
            $this->manager->stopwatch->stop('ODS-get');
            $this->manager->logger->error($e->getMessage());
            throw new \Exception('Connection error, check if config is OK, or if some needed VPN is on.');
        }
    }

    public function getOdsViewMetadata()
    {
        $param = [
            'auth' => $this->auth,
            'headers' => [
                'Prefer' => 'odata.maxpagesize=100000'
            ]
        ];

        $this->manager->stopwatch->start('ODS-get');

        //$res = $this->client->request('GET', $uri, ['auth' => $this->auth]);
        $res = $this->client->request('GET', $this::ODS_METADATA_URI, $param);

        $response = $res->getBody()->getContents();
        $stop = $this->manager->stopwatch->stop('ODS-get');
        $this->manager->addToCollectedData('ods', $res->getStatusCode(), $this::ODS_METADATA_URI,
            null, $response, $stop);

        return $this->getValuesFromXmlResponse($response);
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
        if (0 != json_last_error()) {
            $this->manager->logger->error(substr($response, 0, 255));
            throw new \Exception('Error while parsing response');
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

    public function getValuesFromXmlResponse($response)
    {
        return $this->xmlEncoder->decode($response, 'array');
    }
}
