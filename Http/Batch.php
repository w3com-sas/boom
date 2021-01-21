<?php


namespace W3com\BoomBundle\Http;


use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use W3com\BoomBundle\Service\BoomManager;
use W3com\BoomBundle\Service\BoomConstants;

class Batch
{
    const BATCH_PATH = '$batch';

    private static $CONNECTION_ESTABLISHED_HEADERS = [
        "HTTP/1.0 200 Connection established\r\n\r\n",
        "HTTP/1.1 200 Connection established\r\n\r\n",
    ];

    /**
     * @var string Multipart Boundary.
     */
    private $boundary;

    /**
     * @var array service requests to be executed.
     */
    private $requests = [];

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $batchPath;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * @var array
     */
    private $config;

    /**
     * @var BoomManager
     */
    private $boomManager;

    public function __construct(
        BoomManager $boomManager,
        array $config,
        Stopwatch $stopwatch,
        bool $boundary = false,
        string $batchPath = null
    )
    {
        $this->config = $config;
        $this->stopwatch = $stopwatch;
        $this->boundary = $boundary ?: mt_rand();
        $this->batchPath = $batchPath ?: self::BATCH_PATH;
        $this->boomManager = $boomManager;
        $this->client = $this->boomManager->getCurrentSLClient();
    }

    public function add(Request $request, $key = false): void
    {
        if (false == $key) {
            $key = mt_rand();
        }
        $this->requests[$key] = $request;
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function execute(string $requestType): ResponseInterface
    {
        if (BoomConstants::ODS === $requestType) {
            $rootUrl = $this->config['odata_service']['connections']['default']['path'];
            $url =
                $this->config['odata_service']['connections']['default']['uri']
                .$this->config['odata_service']['connections']['default']['path']
                .$this->batchPath
            ;
        } elseif (BoomConstants::SL === $requestType) {
            $rootUrl = $this->config['service_layer']['connections']['default']['path'];
            $url = $this->batchPath;
        }

        $body = '--batch_'.$this->boundary.PHP_EOL;
        $body .= 'Content-Type: multipart/mixed; boundary='.$this->boundary.PHP_EOL;
        $batchHttpTemplate = <<<EOF

--%s
Content-Type: application/http
Content-Transfer-Encoding: binary

%s
%s
%s
EOF;
        /** @var Request $request */
        foreach ($this->requests as $key => $request) {
            $firstLine = sprintf(
                '%s %s HTTP/%s',
                $request->getMethod(),
                $rootUrl.$request->getRequestTarget(),
                $request->getProtocolVersion()
            );
            $content = (string) $request->getBody();
            $headers = '';
            foreach ($request->getHeaders() as $name => $values) {
                $headers .= sprintf("%s:%s\r\n", $name, implode(', ', $values));
            }
            $body .= sprintf(
                $batchHttpTemplate,
                $this->boundary,
                $firstLine,
                $headers,
                $content.PHP_EOL.PHP_EOL
            );
        }
        $body .= "--{$this->boundary}--".PHP_EOL;
        $body .= PHP_EOL.PHP_EOL."--batch_{$this->boundary}--";
        $body = trim($body);
        if (BoomConstants::ODS === $requestType) {
            $username = $this->config['odata_service']['connections']['default']['username'];
            $password = $this->config['odata_service']['connections']['default']['password'];
            $headers = [
                'Content-Type' => sprintf('multipart/mixed; boundary=%s', 'batch_'.$this->boundary),
                'Authorization' => 'Basic '.base64_encode("$username:$password"),
            ];
        } elseif (BoomConstants::SL === $requestType) {
            $headers = [
                'Content-Type' => sprintf('multipart/mixed; boundary=%s', 'batch_'.$this->boundary)
            ];
        }
        $request = new Request(
            'POST',
            $url,
            $headers,
            $body
        );
        $response = $this->client->send($request);
        $this->requests = [];
        return $this->parseResponse($response, $requestType);
    }

    /**
     * @throws Exception
     */
    public function parseResponse(ResponseInterface $response, string $requestType): ResponseInterface
    {
        $body = (string) $response->getBody();

        if (BoomConstants::ODS === $requestType) {
            if (strpos($body, 'error')) {
                throw new Exception($body);
            }
        } elseif (BoomConstants::SL === $requestType) {
            $contentType = $response->getHeaderLine('content-type');
            $contentType = explode(';', $contentType);
            $boundary = false;
            foreach ($contentType as $part) {
                $part = explode('=', $part, 2);
                if (isset($part[0]) && 'boundary' == trim($part[0])) {
                    $boundary = $part[1];
                }
            }
            $changesetBoundary = str_replace('batchresponse_', '', $boundary);
            if (strpos($body, "--changesetresponse_$changesetBoundary") === false) {
                if(strpos($body,'"error"') !== false){
                    // We try to find error message
                    $search = '"value" : "';
                    $begin = strpos($body,$search)+strlen($search);
                    $error = substr($body, $begin , strpos($body,'"',$begin) - $begin);
                    throw new Exception($error);
                } else {
                    throw new Exception($body);
                }
            }
        }
        $response->getBody()->rewind();

        return $response;
    }

    private function parseRawHeaders($rawHeaders): array
    {
        $headers = [];
        $responseHeaderLines = explode("\r\n", $rawHeaders);
        foreach ($responseHeaderLines as $headerLine) {
            if ($headerLine && strpos($headerLine, ':') !== false) {
                list($header, $value) = explode(': ', $headerLine, 2);
                $header = strtolower($header);
                if (isset($headers[$header])) {
                    $headers[$header] .= "\n" . $value;
                } else {
                    $headers[$header] = $value;
                }
            }
        }
        return $headers;
    }

    /**
     * Used by the IO lib and also the batch processing.
     *
     * @param $respData
     * @param $headerSize
     */
    private function parseHttpResponse($respData, $headerSize): array
    {
        // check proxy header
        foreach (self::$CONNECTION_ESTABLISHED_HEADERS as $established_header) {
            if (stripos($respData, $established_header) !== false) {
                // existed, remove it
                $respData = str_ireplace($established_header, '', $respData);
                // Subtract the proxy header size unless the cURL bug prior to 7.30.0
                // is present which prevented the proxy header size from being taken into
                // account.
                // @TODO look into this
                // if (!$this->needsQuirk()) {
                //   $headerSize -= strlen($established_header);
                // }
                break;
            }
        }
        if ($headerSize) {
            $responseBody = substr($respData, $headerSize);
            $responseHeaders = substr($respData, 0, $headerSize);
        } else {
            $responseSegments = explode("\r\n\r\n", $respData, 2);
            $responseHeaders = $responseSegments[0];
            $responseBody = isset($responseSegments[1]) ? $responseSegments[1] :
                null;
        }
        $responseHeaders = $this->parseRawHeaders($responseHeaders);
        return [$responseHeaders, $responseBody];
    }
}