<?php

namespace SecretaryClient\Client;

use GuzzleHttp;

class Key extends Base
{
    /**
     * @var string
     */
    private $keyEndpoint = '/api/key';

    /**
     * @param int $userId
     * @return array
     */
    public function getById($userId)
    {
        $getUrl = sprintf(
            '%s/%d',
            $this->keyEndpoint,
            $userId
        );
        $response = $this->client->get($getUrl);

        return $this->checkResponseForkey($response);
    }

    /**
     * @param GuzzleHttp\Message\ResponseInterface $response
     * @return array
     * @throws \RuntimeException If api error occurred
     * @throws \LogicException   If no key was found
     */
    private function checkResponseForKey(GuzzleHttp\Message\ResponseInterface $response)
    {
        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException('An error occurred while fetching the user.');
        }

        $responseData = $response->json();
        if (empty($responseData['pubKey'])) {
            throw new \LogicException('Key does not exists.');
        }

        return $responseData['pubKey'];
    }
}