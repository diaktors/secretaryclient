<?php

namespace SecretaryClient\Client;

use GuzzleHttp;

abstract class Base
{
    /**
     * @var string
     */
    protected $apiUrl;

    /**
     * @var \GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $error;

    /**
     * @param string $apiUrl
     * @param string $username
     * @param string $password
     */
    public function __construct($apiUrl, $username, $password)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->client = new GuzzleHttp\Client([
            'base_url' => $this->apiUrl,
            'defaults' => [
                'headers' => ['accept' => 'application/json'],
                //'auth' => ['user', 'pass'],
            ]
        ]);
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @return GuzzleHttp\Client|GuzzleHttp\ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return bool
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        if (!empty($this->error)) {
            return true;
        }

        return false;
    }

    /**
     * @param GuzzleHttp\ClientInterface $client
     * @return $this
     */
    public function setClient(GuzzleHttp\ClientInterface $client)
    {
        $this->client = $client;

        return $this;
    }
}