<?php

namespace W3com\BoomBundle\RestClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use W3com\BoomBundle\Service\BoomManager;

class SLRestClient implements RestClientInterface
{
    const SL_METADATA_URI = '$metadata';
    const STORAGE_KEY = 'sl.metadata';

    /**
     * @var BoomManager
     */
    private $manager;

    /**
     * @var XmlEncoder
     */
    private $xmlEncoder;

    /**
     * @var AdapterInterface
     */
    private $cache;

    public function __construct(BoomManager $manager, AdapterInterface $cache)
    {
        $this->cache = $cache;
        $this->manager = $manager;
        $this->xmlEncoder = new XmlEncoder();
    }

    public function get(string $uri, $file = false)
    {
        /** @var Client $client */
        $client = $this->manager->getCurrentSLClient();
        $attempts = 0;
        while ($attempts < $this->manager->config['service_layer']['max_login_attempts']) {
            try {
                ++$attempts;
                $this->manager->stopwatch->start('SL-get');

                // For the Hosted by SAP the parameters PageSize in b1s.conf is not accessible
                // so we deactivate the pagination by pass the Prefer param in the header
                $param = [
                    'headers' => [
                        'Prefer' => 'odata.maxpagesize=10000',
                        'cache-control' => 'no-cache'
                    ]
                ];

                $res = $client->request('GET', $uri, $param);
                $stop = $this->manager->stopwatch->stop('SL-get');
                $response = $res->getBody()->getContents();
                $this->manager->addToCollectedData('sl', $res->getStatusCode(), $uri, null, $response, $stop);

                if ($file) {
                    return $response;
                } else {
                    return $this->getValuesFromResponse($response);
                }
            } catch (ClientException $e) {
                if (401 == $e->getCode()) {
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
                    if (404 == $e->getCode()) {
                        $this->manager->logger->info($response);

                        return null;
                    } elseif (400 == $e->getCode() && strpos($e->getMessage(), '-304') !== false) {
                        $this->manager->logger->error('Remove cookie file');
                        $this->manager->removeLastCookieFile();
                        $this->login();
                    } else {
                        $this->manager->logger->error('ClientException : (' . $e->getCode() . ') ' . $response);
                        throw new \Exception('Unknown error while launching GET request : ' . $e->getMessage() . '(' . $e->getCode() . ')');
                    }
                }
            } catch (ConnectException $e) {
                $this->manager->stopwatch->stop('SL-get');
                $this->manager->logger->error('ConnectException : (' . $e->getCode() . ') - ' . $e->getMessage());
                throw new \Exception('Connection error, check if config is OK, or maybe some needed VPN in on.');
            } catch (\Exception $e) {
                if (502 == $e->getCode()) {
                    $this->login();
                } else {
                    $this->manager->logger->error('Exception : (' . $e->getCode() . ') - ' . $e->getMessage(), $e->getTrace());
                    throw new \Exception('');
                }
            }
        }
    }

    public function getMetadata()
    {
        $cacheMetadata = $this->cache->getItem($this::STORAGE_KEY);
        if (!$cacheMetadata->isHit()) {
            $client = $this->manager->getCurrentSLClient();
            $attempts = 0;
            while ($attempts < $this->manager->config['service_layer']['max_login_attempts']) {
                try {
                    ++$attempts;
                    $this->manager->stopwatch->start('SL-get');
                    //$res = $this->client->request('GET', $uri, ['auth' => $this->auth]);
                    $res = $client->request('GET', self::SL_METADATA_URI, []);
                    $response = $res->getBody()->getContents();
                    $stop = $this->manager->stopwatch->stop('SL-get');
                    $this->manager->addToCollectedData('sl', $res->getStatusCode(), '$metadata',
                        null, $response, $stop);
                    $metadata = $this->getValuesFromXmlResponse($response);
                    $cacheMetadata->set($metadata);
                    $cacheMetadata->expiresAfter(\DateInterval::createFromDateString('1 day'));
                    $this->cache->save($cacheMetadata);
                    return $metadata;

                } catch (ClientException $e) {
                    if (401 == $e->getCode()) {
                        $this->login();
                    } else {
                        $response = $e->getResponse()->getBody()->getContents();
                        $this->manager->logger->error($response, [self::SL_METADATA_URI]);
                        throw new \Exception('Unknown error while launching POST request');
                    }
                } catch (ConnectException $e) {
                    $this->manager->logger->error($e->getMessage(), $e->getTrace());
                    throw new \Exception('Connection error, check if config is OK, or maybe some needed VPN in on.');
                }
            }
        }
        return $cacheMetadata->get();
    }

    public function request(string $uri, $data, $method = 'POST')
    {
        /** @var Client $client */
        $client = $this->manager->getCurrentSLClient();

        $attempts = 0;
        while ($attempts < $this->manager->config['service_layer']['max_login_attempts']) {
            try {
                ++$attempts;

                $res = $client->request(
                    $method,
                    $uri,
                    $data
                );

                $response = $res->getBody()->getContents();
                if ($method == 'POST') {
                    return $this->getValuesFromResponse($response);
                } else {
                    return [];
                }
            } catch (ClientException $e) {
                if (401 == $e->getCode()) {
                    $this->login();
                } else {
                    $response = $e->getResponse()->getBody()->getContents();
                    $this->manager->logger->error('(' . $e->getCode() . ') - ' . $response, [$data, $uri]);
                    throw new \Exception('Unknown error while launching POST request');
                }
            } catch (ConnectException $e) {
                $this->manager->logger->error($e->getMessage(), $e->getTrace());
                throw new \Exception('Connection error, check if config is OK, or maybe some needed VPN in on.');
            } catch (\Exception $e) {
                if (502 == $e->getCode()) {
                    $this->login();
                } else {
                    $this->manager->logger->error('Exception : (' . $e->getCode() . ') - ' . $e->getMessage());
                    throw new \Exception('');
                }
            }
        }
    }

    public function post(string $uri, $data)
    {
        /** @var Client $client */
        $client = $this->manager->getCurrentSLClient();
        $attempts = 0;
        while ($attempts < $this->manager->config['service_layer']['max_login_attempts']) {
            try {
                ++$attempts;
                $this->manager->stopwatch->start('SL-post');
                $res = $client->request(
                    'POST',
                    $uri,
                    [
                        'json' => $data,
                    ]
                );
                $stop = $this->manager->stopwatch->stop('SL-post');
                $response = $res->getBody()->getContents();
                $this->manager->addToCollectedData('sl', $res->getStatusCode(), $uri, $data, $response, $stop);

                return $this->getValuesFromResponse($response);
            } catch (ClientException $e) {
                if (401 == $e->getCode()) {
                    $this->login();
                }  else {
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
                    $this->manager->logger->error($response, [$data, $uri]);
                    $json_error = json_decode($response, true);
                    $errMessage = array_key_exists('error', $json_error) ?
                        $json_error['error']['message']['value'] :
                        'Unknown error while launching POST request';
                    throw new \Exception($errMessage);
                }
            } catch (ConnectException $e) {
                $this->manager->stopwatch->stop('SL-post');
                $this->manager->logger->error($e->getMessage(), $e->getTrace());
                throw new \Exception('Connection error, check if config is OK, or maybe some needed VPN in on.');
            } catch (\Exception $e) {
                if (502 == $e->getCode()) {
                    $this->login();
                } else {
                    $this->manager->logger->error('Exception : (' . $e->getCode() . ') - ' . $e->getMessage());
                    throw new \Exception('');
                }
            }
        }
    }

    public function patch(string $uri, $data, $updateCollection = false)
    {
        /** @var Client $client */
        $client = $this->manager->getCurrentSLClient();
        $attempts = 0;
        while ($attempts < $this->manager->config['service_layer']['max_login_attempts']) {
            try {
                ++$attempts;
                $this->manager->stopwatch->start('SL-patch');

                $param = [
                    'json' => $data,
                ];
                if ($updateCollection === true) {
                    $param['headers'] = [
                        'B1S-ReplaceCollectionsOnPatch' => 'true'
                    ];
                }

                $res = $client->request(
                    'PATCH',
                    $uri,
                    $param
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
                if (401 == $e->getCode()) {
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

                    $json_error = json_decode($response, true);
                    $errMessage = array_key_exists('error', $json_error) ?
                        $json_error['error']['message']['value'] :
                        'Unknown error while launching PATCH request (' . $e->getCode() . ')';

                    throw new \Exception($errMessage);
                }
            } catch (ConnectException $e) {
                $this->manager->stopwatch->stop('SL-patch');
                $this->manager->logger->error('(' . $e->getCode() . ') - ' . $e->getMessage(), $e->getTrace());
                throw new \Exception('Connection error, check if config is OK, or maybe some needed VPN in on.');
            }catch (\Exception $e) {
                if (502 == $e->getCode()) {
                    $this->login();
                } else {
                    $this->manager->logger->error('Exception : (' . $e->getCode() . ') - ' . $e->getMessage());
                    throw new \Exception('');
                }
            }
        }
    }

    public function cancel(string $uri)
    {
        /** @var Client $client */
        $client = $this->manager->getCurrentSLClient();
        $attempts = 0;
        while ($attempts < $this->manager->config['service_layer']['max_login_attempts']) {
            try {
                ++$attempts;
                $this->manager->stopwatch->start('SL-cancel');
                $res = $client->request(
                    'POST',
                    $uri . '/Cancel'
                );
                $stop = $this->manager->stopwatch->stop('SL-cancel');
                $this->manager->addToCollectedData(
                    'sl',
                    $res->getStatusCode(),
                    $uri,
                    null,
                    $res->getBody()->getContents(),
                    $stop
                );

                return true;
            } catch (ClientException $e) {
                if (401 == $e->getCode()) {
                    $this->login();
                } else {
                    $stop = $this->manager->stopwatch->stop('SL-cancel');
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

                    $json_error = json_decode($response, true);
                    $errMessage = array_key_exists('error', $json_error) ?
                        $json_error['error']['message']['value'] :
                        'Unknown error while launching CANCEL request';

                    throw new \Exception($errMessage);
                }
            } catch (ConnectException $e) {
                $this->manager->stopwatch->stop('SL-cancel');
                $this->manager->logger->error($e->getMessage(), $e->getTrace());
                throw new \Exception('Connection error, check if config is OK, or maybe some needed VPN in on.');
            } catch (\Exception $e) {
                if (502 == $e->getCode()) {
                    $this->login();
                } else {
                    $this->manager->logger->error('Exception : (' . $e->getCode() . ') - ' . $e->getMessage());
                    throw new \Exception('');
                }
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
        $client = $this->manager->getCurrentSLClient();
        $attempts = 0;
        while ($attempts < $this->manager->config['service_layer']['max_login_attempts']) {
            try {
                ++$attempts;
                $this->manager->stopwatch->start('SL-delete');
                $res = $client->request('DELETE', $uri);
                $stop = $this->manager->stopwatch->stop('SL-delete');
                $response = $res->getBody()->getContents();
                $this->manager->addToCollectedData('sl', $res->getStatusCode(), $uri, null, $response, $stop);

                $ret = ('' == $response) ? true : $this->getValuesFromResponse($response);

                return $ret;
            } catch (ClientException $e) {
                if (401 == $e->getCode()) {
                    $this->login();
                }  else {
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
                    throw new \Exception('Unknown error while launching DELETE request');
                }
            } catch (ConnectException $e) {
                $this->manager->stopwatch->stop('SL-delete');
                $this->manager->logger->error($e->getMessage(), $e->getTrace());
                throw new \Exception('Connection error, check if config is OK, or maybe some needed VPN in on.');
            }catch (\Exception $e) {
                if (502 == $e->getCode()) {
                    $this->login();
                } else {
                    $this->manager->logger->error('Exception : (' . $e->getCode() . ') - ' . $e->getMessage());
                    throw new \Exception('');
                }
            }
        }
    }

    public function getValuesFromResponse($response)
    {
        $ar = json_decode($response, true);
        if (0 != json_last_error()) {
            $this->manager->logger->error('Error while parsing response in SL : ' . substr($response, 0, 255));
            throw new \Exception('Error while parsing response');
        }
        if (is_int($ar)) {
            return $ar;
        } // we just counted something
        if (array_key_exists('value', $ar)) {
            return $ar['value'];
        }

        return $ar;
    }

    public function login()
    {
        $loginData = $this->manager->config['service_layer']['connections'][$this->manager->getCurrentSLConnection()];
        $collectedData = $loginData;
        unset($collectedData['password']);
        try {
            $this->manager->stopwatch->start('SL-login');
            $res = $this->manager->getCurrentSLClient()->post(
                'Login',
                [
                    'json' => [
                        'UserName' => $loginData['username'],
                        'Password' => $loginData['password'],
                        'CompanyDB' => $loginData['database'],
                    ],
                ]
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
            if (200 == $res->getStatusCode()) {
                // on est loggués
                $this->manager->logger->info('Successfully loggued as ' . $loginData['username'] . '.');
            } else {
                // le log a planté :(
                $this->manager->logger->info('Loging as ' . $loginData['username'] . ' failed.');
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
            throw new \Exception('Unknown error while loging in (' . $e->getCode() . ') - ' . $e->getMessage());
        }catch (\Exception $e) {
            if (502 == $e->getCode()) {
                $this->login();
            } else {
                $this->manager->logger->error('Exception : (' . $e->getCode() . ') - ' . $e->getMessage(), $e->getTrace());
                throw new \Exception('');
            }
        }
    }

    public function getValuesFromXmlResponse($response)
    {
        return $this->xmlEncoder->decode($response, 'array');
    }
}
