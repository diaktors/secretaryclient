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
 * Key Client
 */
class Key extends Base
{
    /**
     * @var string
     */
    private $keyEndpoint = '/api/key';

    /**
     * @param int $userId
     * @return string
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