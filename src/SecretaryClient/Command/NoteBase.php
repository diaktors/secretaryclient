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
 * @category Command
 * @package  SecretaryClient\Command
 * @author   Michael Scholl <michael@wesrc.com>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/wesrc/secretary
 */

namespace SecretaryClient\Command;

use SecretaryClient\Client;
use SecretaryCrypt\Crypt;
use Symfony\Component\Console;

/**
 * Command Note Base class
 */
abstract class NoteBase extends Base
{
    /**
     * @var Crypt
     */
    protected $cryptService;

    /**
     * @param Crypt $cryptService
     */
    public function setCryptService($cryptService)
    {
        $this->cryptService = $cryptService;
    }

    /**
     * @return Crypt
     */
    public function getCryptService()
    {
        return $this->cryptService;
    }

    /**
     * @param Crypt $cryptService
     */
    public function __construct(Crypt $cryptService)
    {
        $this->cryptService = $cryptService;
        parent::__construct();
    }

    /**
     * @param array $note
     * @param string $passphrase
     * @return string
     */
    protected function decryptNote(array $note, $passphrase)
    {
        return $this->cryptService->decrypt(
            $note['content'],
            $note['eKey'],
            file_get_contents($this->config['privateKeyPath']),
            $passphrase
        );
    }

    /**
     * @param int $noteId
     * @return array
     */
    protected function getNote($noteId)
    {
        /** @var Client\Note $client */
        $client = $this->getClient('note', $this->config);
        $note = $client->get($noteId, $this->config['userId']);
        $client->checkForError();

        return $note;
    }

    /**
     * @return array
     */
    protected function getPassphraseValue()
    {
        $question = new Console\Question\Question('Private Key Passphrase: ');
        $question->setHidden(true)
            ->setHiddenFallback(false)
            ->setValidator(function ($answer) {
                if (empty($answer)) {
                    throw new \InvalidArgumentException('You need to provide a key passphrase value');
                }
                return $answer;
            });
        return $this->askQuestion($question);
    }

    /**
     * @param int $userId
     * @return string
     */
    protected function getUserPublicKey($userId)
    {
        /** @var Client\Key $keyClient */
        $keyClient = $this->getClient('key', $this->config);
        $userKey = $keyClient->getById($userId);
        $keyClient->checkForError($keyClient);

        return $userKey;
    }
}
