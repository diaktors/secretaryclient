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
     * @return array
     *
     * @throws \LogicException
     */
    public function createPrivateNote($private, $title, $content, $eKey)
    {
        try {
            $response = $this->client->post($this->apiUrl . $this->noteEndpoint, [
                'body' => json_encode([
                    'title' => $title,
                    'content' => $content,
                    'private' => $private,
                    'eKey' => $eKey
                ])
            ]);
        } catch (GuzzleHttp\Exception\RequestException $e) {
            $this->error = $e->getMessage();
            return false;
        }

        if ($response->getStatusCode() != 201) {
            $this->error = 'An error occurred while saving the note.';
            return false;
        }

        return $response->json();
    }

    /**
     * @param int $private
     * @param string $title
     * @param string $content
     * @param int $groupId
     * @param array $encryptData
     * @param array $users
     * @return array
     *
     * @throws \LogicException
     */
    public function createGroupNote($private, $title, $content, $groupId, array $encryptData, array $users)
    {
        try {
            $response = $this->client->post($this->apiUrl . $this->noteEndpoint, [
                'body' => json_encode([
                    'private' => $private,
                    'title' => $title,
                    'content' => $content,
                    'groupId' => $groupId,
                    'encryptData' => $encryptData,
                    'users' => $users
                ])
            ]);
        } catch (GuzzleHttp\Exception\RequestException $e) {
            $this->error = $e->getMessage();
            return false;
        }

        if ($response->getStatusCode() != 201) {
            $this->error = 'An error occurred while saving the note.';
            return false;
        }

        return $response->json();
    }

    /**
     * @param int $noteId
     * @return array
     */
    public function get($noteId)
    {
        $listUrl = sprintf(
            '%s%s/%d',
            $this->apiUrl,
            $this->noteEndpoint,
            $noteId
        );

        try {
            $response = $this->client->get($listUrl);
        } catch (GuzzleHttp\Exception\RequestException $e) {
            if ($e->getResponse()->getStatusCode() == 404) {
                $this->error = 'Note could not been found.';
                return false;
            }
            if ($e->getResponse()->getStatusCode() == 403) {
                $this->error = 'You are not allowed to view this note.';
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
     * @param null|int $private
     * @return array
     *
     * @throws \LogicException
     */
    public function listNotes($page = 1, $group, $private = null)
    {
        $buildQuery = [
            'orderBy' => ['id' => 'asc'],
            'page' => $page
        ];
        $buildQuery = $this->buildQuery($buildQuery, $group, $private);

        $listUrl = sprintf(
            '%s%s?%s',
            $this->apiUrl,
            $this->noteEndpoint,
            http_build_query($buildQuery)
        );

        try {
            $response = $this->client->get($listUrl);
        } catch (GuzzleHttp\Exception\RequestException $e) {
            $this->error = $e->getMessage();
            return false;
        }

        if ($response->getStatusCode() != 200) {
            $this->error = 'An error occurred while querying note list.';
            return false;
        }

        return $response->json();
    }

    /**
     * @param string $search
     * @param int $page
     * @param null|int $group
     * @param null|int $private
     * @return array
     *
     * @throws \LogicException
     */
    public function searchNotes($search, $page = 1, $group, $private = null)
    {
        $buildQuery = [
            'orderBy' => ['id' => 'asc'],
            'page' => $page,
            'query' => [
                ['field' => 'title', 'value' => '%' . $search . '%', 'where' => 'and', 'type' => 'like'],
            ]
        ];
        $buildQuery = $this->buildQuery($buildQuery, $group, $private);

        $listUrl = sprintf(
            '%s%s?%s',
            $this->apiUrl,
            $this->noteEndpoint,
            http_build_query($buildQuery)
        );

        try {
            $response = $this->client->get($listUrl);
        } catch (GuzzleHttp\Exception\RequestException $e) {
            $this->error = $e->getMessage();
            return false;
        }

        if ($response->getStatusCode() != 200) {
            $this->error = 'An error occurred while querying note list.';
            return false;
        }

        return $response->json();
    }

    /**
     * @param array $buildQuery
     * @param null|int $group
     * @param null|int $private
     * @return array
     */
    private function buildQuery(array $buildQuery, $group, $private)
    {
        if (!empty($group)) {
            $groupQuery = ['field' => 'group', 'value' => $group, 'where' => 'and', 'type' => 'eq'];
            if (isset($buildQuery['query'])) {
                $buildQuery['query'][] = $groupQuery;
            } else {
                $buildQuery['query'] = [$groupQuery];
            }
        }
        if (is_numeric($private)) {
            $privateQuery = ['field' => 'private', 'value' => $private, 'where' => 'and', 'type' => 'eq'];
            if (isset($buildQuery['query'])) {
                $buildQuery['query'][] = $privateQuery;
            } else {
                $buildQuery['query'] = [$privateQuery];
            }
        }

        return $buildQuery;
    }
}