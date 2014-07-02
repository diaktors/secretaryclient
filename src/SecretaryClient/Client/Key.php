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
        try {
            $response = $this->client->get($getUrl);
        } catch (GuzzleHttp\Exception\RequestException $e) {
            $this->error = $e->getMessage();
            return false;
        }

        if ($response->getStatusCode() != 200) {
            $this->error = 'An error occurred while fetching the user.';
            return false;
        }

        $responseData = $response->json();
        if (empty($responseData['pubKey'])) {
            $this->error = 'Key does not exists.';
            return false;
        }


        return $responseData['pubKey'];
    }
}