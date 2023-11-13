<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

class Controller {
    public $logger;
    public $httpRequest;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->httpRequest = new Client();
        $this->logger = $logger;
    }

    /**
     * Curl
     * Create a Curl request with Guzzle
     * 
     * @param string $url The url to request
     * @param string $method The method to use
     * @param array $headers The headers to send
     * @param array $data The data to send
     * 
     * @return array
     */
    public function curl(string $url, string $method = 'GET', array $headers = [], array $data = []): array
    {
        try {
            $response = $this->httpRequest->request(
                $method,
                $url,
                [
                    'headers' => $headers,
                    'form_params' => $data
                ]
            );
        
            return ['success' => true, 'response' => json_decode($response->getBody()->getContents(), true)];
        } catch (RequestException $e) {
            $this->logger->error(__CLASS__ . ':' . __FUNCTION__ . ':' . $e->getMessage(), ['method' => $method, 'url' => $url, 'headers' => $headers, 'data' => $data]);
            return ['success' => false, 'response' => $e->getMessage()];
        }
    }
}
