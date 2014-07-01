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
     * @param int $userId
     * @throws \LogicException If api error occurred
     * @return array
     */
    public function get($userId)
    {
        $buildQuery = [
            'orderBy' => ['id' => 'asc'],
            //'page' => $page
        ];
        if (!empty($group)) {
            $buildQuery['query'] = [
                ['field' => 'users', 'value' => $userId, 'type' => 'eq'],
            ];
        }

        $getUrl = sprintf(
            '%s?%s',
            $this->groupEndpoint,
            http_build_query($buildQuery)
        );
        $response = $this->client->get($getUrl);

        if ($response->getStatusCode() != 200) {
            throw new \LogicException('An error occurred while querying group list.');
        }

        return $response->json();
    }
}