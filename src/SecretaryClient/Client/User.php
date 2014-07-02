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

        return $this->doGetRequest($getUrl);
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

        return $this->doGetRequest($getUrl);
    }

    /**
     * @param array $userIds
     * @return array
     */
    public function getUsers(array $userIds)
    {
        $userQuery = ['query' => []];
        foreach ($userIds as $userId) {
            $userQuery['query'][] = ['field' => 'id', 'value' => $userId, 'where' => 'or', 'type' => 'eq'];
        }
        $getUrl = sprintf(
            '%s?%s',
            $this->userEndpoint,
            http_build_query($userQuery)
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

        return $responseData['_embedded']['user'];
    }

    /**
     * @param string $url
     * @return array
     */
    private function doGetRequest($url)
    {
        try {
            $response = $this->client->get($url);
        } catch (GuzzleHttp\Exception\RequestException $e) {
            $this->error = $e->getMessage();
            return false;
        }

        if ($response->getStatusCode() != 200) {
            $this->error = 'An error occurred while fetching the user.';
            return false;
        }

        $responseData = $response->json();
        if ($responseData['count'] == 0) {
            $this->error = 'User does not exists.';
            return false;
        }

        return $responseData['_embedded']['user'][0];
    }
}