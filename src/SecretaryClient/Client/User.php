<?php

namespace SecretaryClient\Client;

use GuzzleHttp;

class User extends Base
{
    /**
     * @var string
     */
    private $userEndpoint = '/api/user';

    /**
     * @param int $userId
     * @return array
     */
    public function getById($userId)
    {
        $getUrl = sprintf(
            '%s/%d',
            $this->userEndpoint,
            $userId
        );
        $response = $this->client->get($getUrl);

        return $this->checkResponseForUser($response);
    }

    /**
     * @param string $mail
     * @return array
     */
    public function getByMail($mail)
    {
        $getUrl = sprintf(
            '%s?%s',
            $this->userEndpoint,
            http_build_query([
                'query' => [
                    ['field' => 'email', 'value' => $mail, 'type' => 'eq']
                ]
            ])
        );
        $response = $this->client->get($getUrl);

        return $this->checkResponseForUser($response);
    }

    /**
     * @param GuzzleHttp\Message\ResponseInterface $response
     * @return array
     * @throws \RuntimeException If api error occurred
     * @throws \LogicException   If no user was found
     */
    private function checkResponseForUser(GuzzleHttp\Message\ResponseInterface $response)
    {
        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException('An error occurred while fetching the user.');
        }

        $responseData = $response->json();
        if ($responseData['count'] == 0) {
            throw new \LogicException('User does not exists.');
        }

        return $responseData['_embedded']['user'][0];
    }
}