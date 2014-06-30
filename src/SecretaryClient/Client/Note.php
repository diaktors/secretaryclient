<?php

namespace SecretaryClient\Client;

use GuzzleHttp;

class Note extends Base
{
    /**
     * @var string
     */
    private $noteEndpoint = '/api/note';

    /**
     * @param int $private
     * @param string $title
     * @param string $content
     * @param string $eKey
     * @param int $userId
     * @return array
     *
     * @throws \LogicException
     */
    public function create($private, $title, $content, $eKey, $userId)
    {
        $response = $this->client->post($this->apiUrl . $this->noteEndpoint, [
            'body' => [
                'title' => $title,
                'content' => $content,
                'private' => $private,
                'eKey' => $eKey,
                'userId' => $userId
            ]
        ]);

        if ($response->getStatusCode() != 201) {
            throw new \LogicException('An error occurred while saving the note.');
        }

        return $response->json();
    }

    /**
     * @param int $noteId
     * @param int $userId
     * @return array
     */
    public function get($noteId, $userId)
    {
        $listUrl = sprintf(
            '%s%s/%d/user/%d',
            $this->apiUrl,
            $this->noteEndpoint,
            $noteId,
            $userId
        );

        try {
            $response = $this->client->get($listUrl);
        } catch (GuzzleHttp\Exception\RequestException $e) {
            if ($e->getResponse()->getStatusCode() == 404) {
                $this->error = 'Note could not been found.';
                return false;
            }
            $this->error = $e->getMessage();
            return false;
        }

        if ($response->getStatusCode() != 200) {
            $this->error = 'An error occurred while fetching note.';
            return false;
        }

        return $response->json();
    }

    /**
     * @param int $page
     * @param int $group
     * @param int $userId
     * @return array
     *
     * @throws \LogicException
     */
    public function listNotes($page = 1, $group, $userId)
    {
        $buildQuery = [
            'orderBy' => ['id' => 'asc'],
            'page' => $page
        ];
        if (!empty($group)) {
            $buildQuery['query'] = [
                ['field' => 'group', 'value' => $group, 'type' => 'eq'],
            ];
        }

        $listUrl = sprintf(
            '%s%s?%s',
            $this->apiUrl,
            $this->noteEndpoint,
            http_build_query($buildQuery)
        );
        $response = $this->client->get($listUrl);

        if ($response->getStatusCode() != 200) {
            throw new \LogicException('An error occurred while querying note list.');
        }

        return $response->json();
    }

    /**
     * @param string $search
     * @param int $page
     * @param int $group
     * @param int $userId
     * @return array
     *
     * @throws \LogicException
     */
    public function searchNotes($search, $page = 1, $group, $userId)
    {
        $buildQuery = [
            'orderBy' => ['id' => 'asc'],
            'page' => $page,
            'query' => [
                ['field' => 'title', 'value' => '%' . $search . '%', 'where' => 'and', 'type' => 'like'],
            ]
        ];
        if (!empty($group)) {
            $buildQuery['query'][] = ['field' => 'group', 'value' => $group, 'where' => 'and', 'type' => 'eq'];
        }

        $listUrl = sprintf(
            '%s%s?%s',
            $this->apiUrl,
            $this->noteEndpoint,
            http_build_query($buildQuery)
        );
        $response = $this->client->get($listUrl);

        if ($response->getStatusCode() != 200) {
            throw new \LogicException('An error occurred while querying note list.');
        }

        return $response->json();
    }
}