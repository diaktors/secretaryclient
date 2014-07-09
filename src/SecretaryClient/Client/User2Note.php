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
 * User2Note Client
 */
class User2Note extends Base
{
    /**
     * @var string
     */
    private $user2noteEndpoint = '/api/user2note';

    /**
     * @param int $userId
     * @param int $noteId
     * @param string $eKey
     * @param bool $owner
     * @param bool $readPermission
     * @param bool $writePermission
     * @return array
     *
     * @throws \LogicException
     */
    public function createUser2Note($userId, $noteId, $eKey, $owner, $readPermission, $writePermission)
    {
        try {
            $response = $this->client->post($this->apiUrl . $this->user2noteEndpoint, [
                'body' => json_encode([
                    'userId' => $userId,
                    'noteId' => $noteId,
                    'eKey' => $eKey,
                    'owner' => (int) $owner,
                    'readPermission' => (int) $readPermission,
                    'writePermission' => (int) $writePermission,
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
     * @param int $note
     * @param int $page
     * @return array
     *
     * @throws \LogicException
     */
    public function getNoteRecords($note, $page = 1)
    {
        $buildQuery = [
            'orderBy' => ['userId' => 'asc'],
            'page' => $page
        ];
        $buildQuery = $this->buildQuery($buildQuery, $note);

        $url = sprintf(
            '%s%s?%s',
            $this->apiUrl,
            $this->user2noteEndpoint,
            http_build_query($buildQuery)
        );

        try {
            $response = $this->client->get($url);
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
     * @param int $userId
     * @param int $noteId
     * @param string $eKey
     * @return array
     *
     * @throws \LogicException
     */
    public function updateUser2Note($userId, $noteId, $eKey)
    {
        $url = sprintf(
            '%s%s/%d',
            $this->apiUrl,
            $this->user2noteEndpoint,
            $noteId
        );
        try {
            $response = $this->client->patch($url, [
                'body' => json_encode([
                    'userId' => $userId,
                    'eKey' => $eKey,
                ])
            ]);
        } catch (GuzzleHttp\Exception\RequestException $e) {
            $this->error = $e->getMessage();
            return false;
        }

        if ($response->getStatusCode() != 200) {
            $this->error = 'An error occurred while saving the note.';
            return false;
        }

        return $response->json();
    }

    /**
     * @param array $buildQuery
     * @param null|int $note
     * @return array
     */
    private function buildQuery(array $buildQuery, $note)
    {
        if (!empty($note) && is_numeric($note)) {
            $groupQuery = ['field' => 'noteId', 'value' => $note, 'where' => 'and', 'type' => 'eq'];
            if (isset($buildQuery['query'])) {
                $buildQuery['query'][] = $groupQuery;
            } else {
                $buildQuery['query'] = [$groupQuery];
            }
        }

        return $buildQuery;
    }
}