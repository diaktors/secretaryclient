<?php

namespace SecretaryClient\Client;

use GuzzleHttp;

class Group extends Base
{
    /**
     * @var string
     */
    private $groupEndpoint = '/api/group';

    /**
     * @param int $groupId
     * @return array
     */
    public function get($groupId)
    {
        $getUrl = sprintf(
            '%s/%d',
            $this->groupEndpoint,
            $groupId
        );

        try {
            $response = $this->client->get($getUrl);
        } catch (GuzzleHttp\Exception\RequestException $e) {
            $this->error = $e->getMessage();
            return false;
        }

        if ($response->getStatusCode() != 200) {
            $this->error = 'An error occurred while querying group record.';
            return false;
        }

        return $response->json();
    }

    /**
     * @return array
     */
    public function getUserGroups()
    {
        $buildQuery = [
            'orderBy' => ['id' => 'asc'],
            //'page' => $page
        ];

        $getUrl = sprintf(
            '%s?%s',
            $this->groupEndpoint,
            http_build_query($buildQuery)
        );

        try {
            $response = $this->client->get($getUrl);
        } catch (GuzzleHttp\Exception\RequestException $e) {
            $this->error = $e->getMessage();
            return false;
        }

        if ($response->getStatusCode() != 200) {
            $this->error = 'An error occurred while querying group list.';
            return false;
        }

        $responseData = $response->json();
        if ($responseData['count'] == 0) {
            $this->error = 'No group available, create a private note';
            return;
        }

        return $responseData;
    }
}