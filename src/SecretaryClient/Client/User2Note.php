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
                    'owner' => $owner,
                    'readPermission' => $readPermission,
                    'writePermission' => $writePermission,
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
}