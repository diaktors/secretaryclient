<?php
/**
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 * PHP Version 5
 *
 * @category Client
 * @package  SecretaryClient\Client
 * @author   Michael Scholl <michael@wesrc.com>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/wesrc/secretary
 */

namespace SecretaryClient\Client;

use GuzzleHttp;

/**
 * Note Client
 */
class Note extends Base
{
    /**
     * @var string
     */
    private $noteEndpoint = '/api/note';

    /**
     * @param string $title
     * @param string $content
     * @return array
     *
     * @throws \LogicException
     */
    public function createPrivateNote($title, $content)
    {
        try {
            $response = $this->client->post($this->apiUrl . $this->noteEndpoint, [
                'body' => json_encode([
                    'title' => $title,
                    'content' => $content,
                    'private' => 1
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
     * @param string $title
     * @param string $content
     * @param int $groupId
     * @return array
     *
     * @throws \LogicException
     */
    public function createGroupNote($title, $content, $groupId)
    {
        try {
            $response = $this->client->post($this->apiUrl . $this->noteEndpoint, [
                'body' => json_encode([
                    'private' => 0,
                    'title' => $title,
                    'content' => $content,
                    'group' => $groupId
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