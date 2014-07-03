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
 * Abstract Client Base class
 */
abstract class Base
{
    /**
     * @var string
     */
    protected $apiUrl;

    /**
     * @var \GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $error;

    /**
     * @param string $apiUrl
     * @param string $username
     * @param string $password
     */
    public function __construct($apiUrl, $username, $password)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->client = new GuzzleHttp\Client([
            'base_url' => $this->apiUrl,
            'defaults' => [
                'headers' => ['accept' => 'application/json', 'content-type' => 'application/json'],
                'auth' => [$username, $password],
            ]
        ]);
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @throws Exception
     */
    public function checkForError()
    {
        if ($this->hasError()) {
            throw new Exception(sprintf('<error>%s</error>', $this->getError()));
        }
    }

    /**
     * @return GuzzleHttp\Client|GuzzleHttp\ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return bool
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        if (!empty($this->error)) {
            return true;
        }

        return false;
    }

    /**
     * @param GuzzleHttp\ClientInterface $client
     * @return $this
     */
    public function setClient(GuzzleHttp\ClientInterface $client)
    {
        $this->client = $client;

        return $this;
    }
}