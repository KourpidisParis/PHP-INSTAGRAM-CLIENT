<?php

declare(strict_types=1);

namespace InstagramClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use InstagramClient\Exception\InstagramClientException;
use Psr\Http\Message\ResponseInterface;

class InstagramClient
{
    private const BASE_URL = 'https://graph.instagram.com';
    private const API_VERSION = 'v18.0';
    
    private Client $httpClient;
    private string $accessToken;

    public function __construct(string $accessToken, ?Client $httpClient = null)
    {
        $this->accessToken = $accessToken;
        $this->httpClient = $httpClient ?? new Client([
            'base_uri' => self::BASE_URL,
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'Instagram-PHP-Client/1.0',
            ],
        ]);
    }

    /**
     * Get user profile information
     */
    public function getUserProfile(): array
    {
        $fields = 'id,username,account_type,media_count';
        
        return $this->makeRequest('GET', '/me', [
            'fields' => $fields,
            'access_token' => $this->accessToken,
        ]);
    }

    /**
     * Get user media posts
     */
    public function getUserMedia(int $limit = 25): array
    {
        $fields = 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,username';
        
        return $this->makeRequest('GET', '/me/media', [
            'fields' => $fields,
            'limit' => min($limit, 200), // Instagram API limit
            'access_token' => $this->accessToken,
        ]);
    }

    /**
     * Get details of a specific media item
     */
    public function getMediaDetails(string $mediaId): array
    {
        $fields = 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,username,children{id,media_type,media_url,thumbnail_url}';
        
        return $this->makeRequest('GET', "/{$mediaId}", [
            'fields' => $fields,
            'access_token' => $this->accessToken,
        ]);
    }

    /**
     * Refresh access token (for long-lived tokens)
     */
    public function refreshAccessToken(): array
    {
        return $this->makeRequest('GET', '/refresh_access_token', [
            'grant_type' => 'ig_refresh_token',
            'access_token' => $this->accessToken,
        ]);
    }

    /**
     * Get information about access token
     */
    public function getTokenInfo(): array
    {
        return $this->makeRequest('GET', '/access_token', [
            'access_token' => $this->accessToken,
        ]);
    }

    /**
     * Make HTTP request with error handling
     */
    private function makeRequest(string $method, string $endpoint, array $params = []): array
    {
        try {
            $options = [];
            
            if ($method === 'GET') {
                $options['query'] = $params;
            } else {
                $options['form_params'] = $params;
            }

            $response = $this->httpClient->request($method, $endpoint, $options);
            
            return $this->parseResponse($response);
            
        } catch (RequestException $e) {
            $this->handleRequestException($e);
        } catch (GuzzleException $e) {
            throw InstagramClientException::networkError($e->getMessage());
        }
    }

    /**
     * Parse API response
     */
    private function parseResponse(ResponseInterface $response): array
    {
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw InstagramClientException::apiError('Invalid JSON response');
        }

        if (isset($data['error'])) {
            $errorMessage = $data['error']['message'] ?? 'Unknown error';
            $errorCode = $data['error']['code'] ?? 0;
            
            if ($errorCode === 190 || str_contains(strtolower($errorMessage), 'access token')) {
                throw InstagramClientException::invalidAccessToken();
            }
            
            throw InstagramClientException::apiError($errorMessage, $errorCode);
        }

        return $data;
    }

    /**
     * Handle request exceptions
     */
    private function handleRequestException(RequestException $e): void
    {
        if ($e->hasResponse()) {
            $response = $e->getResponse();
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            if ($data && isset($data['error'])) {
                $errorMessage = $data['error']['message'] ?? 'Unknown error';
                $errorCode = $data['error']['code'] ?? $response->getStatusCode();
                
                if ($errorCode === 190 || str_contains(strtolower($errorMessage), 'access token')) {
                    throw InstagramClientException::invalidAccessToken();
                }
                
                throw InstagramClientException::apiError($errorMessage, $errorCode);
            }
        }

        throw InstagramClientException::networkError($e->getMessage());
    }

    /**
     * Set new access token
     */
    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    /**
     * Get current access token
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }
}
