<?php


namespace W3com\BoomBundle\Http;


use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class Batch
{
    const BATCH_PATH = '$batch';

    private static $CONNECTION_ESTABLISHED_HEADERS = array(
        "HTTP/1.0 200 Connection established\r\n\r\n",
        "HTTP/1.1 200 Connection established\r\n\r\n",
    );

    /** @var string Multipart Boundary. */
    private $boundary;

    /** @var array service requests to be executed. */
    private $requests = array();

    /** @var Client */
    private $client;

    /** @var string  */
    private $rootUrl;

    /** @var string */
    private $batchPath;

    /*** @var Stopwatch */
    private $stopwatch;

    public function __construct(
        Client $client,
        $config,
        Stopwatch $stopwatch,
        $boundary = false,
        $batchPath = null
    )
    {
        $this->stopwatch = $stopwatch;
        $this->client = $client;
        $this->boundary = $boundary ?: mt_rand();
        $this->rootUrl = $config['service_layer']['path'];
        $this->batchPath = $batchPath ?: self::BATCH_PATH;
    }

    public function add(Request $request, $key = false)
    {
        if (false == $key) {
            $key = mt_rand();
        }
        $this->requests[$key] = $request;
    }

    public function execute()
    {
        $body = '--batch_'.$this->boundary.PHP_EOL;
        $body .= 'Content-Type: multipart/mixed; boundary=' . $this->boundary;
        $classes = array();
        $batchHttpTemplate = <<<EOF

--%s
Content-Type: application/http
Content-Transfer-Encoding: binary
Content-ID: %s
%s
%s
%s
EOF;
        /** @var Request $request */
        foreach ($this->requests as $key => $request) {
            $firstLine = sprintf(
                '%s %s HTTP/%s',
                $request->getMethod(),
                $this->rootUrl.$request->getRequestTarget(),
                $request->getProtocolVersion()
            );
            $content = (string)$request->getBody();
            $headers = '';
            foreach ($request->getHeaders() as $name => $values) {
                $headers .= sprintf("%s:%s\r\n", $name, implode(', ', $values));
            }
            $body .= sprintf(
                $batchHttpTemplate,
                $this->boundary,
                $key,
                $firstLine,
                $headers,
                $content.PHP_EOL.PHP_EOL
            );
            $classes['response-' . $key] = $request->getHeaderLine('X-Php-Expected-Class');
        }
        $body .= PHP_EOL."--{$this->boundary}--";
        $body .= PHP_EOL."--batch_{$this->boundary}--";
        $body = trim($body);
        $url = $this->rootUrl . $this->batchPath;
        $headers = array(
            'Content-Type' => sprintf('multipart/mixed; boundary=%s', 'batch_'.$this->boundary)
        );
        $request = new Request(
            'POST',
            $url,
            $headers,
            $body
        );
        $response = $this->client->send($request);
        return $this->parseResponse($response, $classes);
    }


    public function parseResponse(ResponseInterface $response, $classes = array())
    {
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
        $body = (string)$response->getBody();
        if (strpos($body, "--changesetresponse_$changesetBoundary") === false){
            if(strpos($body,'"error"') !== false){
                // We try to find error message
                $search = '"value" : "';
                $begin = strpos($body,$search)+strlen($search) + 1;
                $error = substr($body, $begin , strpos($body,'"',$begin) - $begin);
                throw new \Exception($error);
            } else {
                throw new \Exception($body);
            }
        }
        /*
        if (!empty($body)) {
            $body = str_replace("--$boundary--", "--$boundary", $body);

            $parts = explode("--changesetresponse_$changesetBoundary", $body);
            $responses = array();
            $requests = array_values($this->requests);
            foreach ($parts as $i => $part) {
                $part = trim($part);
                dump($part);
                if (!empty($part)) {
                    list($rawHeaders, $part) = explode("\r\n", $part, 2);
                    dump($rawHeaders, $part);
                    $headers = $this->parseRawHeaders($rawHeaders);
                    $status = substr($part, 0, strpos($part, "\n"));
                    $status = explode(" ", $status);
                    $status = $status[1];
                    list($partHeaders, $partBody) = $this->parseHttpResponse($part, false);
                    $response = new Response(
                        $status,
                        $partHeaders,
                        Psr7\stream_for($partBody)
                    );
                    // Need content id.
                    $key = $headers['content-id'];
                    try {
                        $response = Google_Http_REST::decodeHttpResponse($response, $requests[$i - 1]);
                    } catch (Google_Service_Exception $e) {
                        // Store the exception as the response, so successful responses
                        // can be processed.
                        $response = $e;
                    }
                    $responses[$key] = $response;
                }
            }
            return $responses;
        }*/
        $response->getBody()->rewind();
        return $response;
    }

    private function parseRawHeaders($rawHeaders)
    {
        $headers = array();
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
     * @return array
     */
    private function parseHttpResponse($respData, $headerSize)
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
        return array($responseHeaders, $responseBody);
    }
}