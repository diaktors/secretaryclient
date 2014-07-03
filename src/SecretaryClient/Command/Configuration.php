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
use Symfony\Component\Console;

/**
 * Configuration Command
 */
class Configuration extends Base
{
    private $configurationFile = 'client_configuration.php';

    public function getConfigurationFile()
    {
        return $this->configurationFile;
    }

    /**
     * Configure configure ccommand
     */
    protected function configure()
    {
        $this
            ->setName('configure')
            ->setDescription('Configure SecretaryClient and save configuration for later usage.')
        ;
    }

    /**
     * @param Console\Input\InputInterface $input
     * @param Console\Output\OutputInterface $output
     * @return int|null|void
     *
     * @throws \InvalidArgumentException If user input is missing
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        if (file_exists($this->configurationFile)) {
            $output->writeln('You already configured your SecretaryClient.');
            return;
        }

        $this->input = $input;
        $this->output = $output;

        $output->writeln("<error>Configuration of SecretaryClient is missing - please provide your settings.</error>\n");

        $username = $this->getUsernameValue();
        $password = $this->getPasswordValue();
        $editor = $this->getEditorValue();
        $tmpDir = $this->getTmpDirValue();
        $privateKeyPath = $this->getPrivateKeyPathValue();
        $apiUrl = $this->getApiUrlValue();

        $output->writeln("\nGiven username: " . $username);
        $output->writeln('Given editor: ' . $editor);
        $output->writeln('Given tmp dir: ' . $tmpDir);
        $output->writeln('Given private key file path: ' . $privateKeyPath);
        $output->writeln('Given SecretaryApi url: ' . $apiUrl);

        $configuration = array(
            'username' => $username,
            'password' => $password,
            'editor' => $editor,
            'tmpDir' => $tmpDir,
            'privateKeyPath' => $privateKeyPath,
            'apiUrl' => $apiUrl,
        );

        try {
            /** @var Client\User $client */
            $client = $this->getClient('user', $configuration);
            $user = $client->getByMail($username);
            $client->checkForError();
            $configuration['userId'] = $user['id'];

            /** @var Client\Key $client */
            $client = $this->getClient('key', $configuration);
            $pubKey = $client->getById($user['id']);
            $client->checkForError();
            $configuration['publicKey'] = $pubKey;

            file_put_contents($this->configurationFile, json_encode($configuration, JSON_PRETTY_PRINT));
            // finally load set config values
            $this->getConfiguration();
        } catch(Client\Exception $e) {
            $output->writeln($e->getMessage());
            return;
        }

        $output->writeln("\nYou successfully configured your SecretaryClient. You can now start using it.\n");

        return;
    }

    /**
     * @return string
     */
    public function getApiUrlValue()
    {
        return $this->askQuestion(
            new Console\Question\Question('Your secretary url: ', 'https://secretary.dev:8080/')
        );
    }

    /**
     * @return string
     */
    public function getEditorValue()
    {
        return $this->askQuestion(
            new Console\Question\Question('Your wished note editor (default vi): ', 'vi')
        );
    }

    /**
     * @return string
     */
    public function getPasswordValue()
    {
        $question = new Console\Question\Question('Password: ');
        $question->setHidden(true)
            ->setHiddenFallback(false)
            ->setValidator(function ($answer) {
                if (empty($answer)) {
                    throw new \InvalidArgumentException('You need to provide a password value');
                }
                return $answer;
            });

        return $this->askQuestion($question);
    }

    /**
     * @return string
     */
    public function getPrivateKeyPathValue()
    {
        $question = new Console\Question\Question('Your private key file path (/complete/path/to/mykey.private): ');
        $question->setValidator(function ($answer) {
            if (empty($answer)) {
                throw new \InvalidArgumentException('You need to provide a key file path');
            }
            return $answer;
        });

        return $this->askQuestion($question);
    }

    /**
     * @return string
     */
    public function getUsernameValue()
    {
        $question = new Console\Question\Question('Username: ');
        $question->setValidator(function ($answer) {
            if (empty($answer)) {
                throw new \InvalidArgumentException('You need to provide a password value');
            }
            return $answer;
        });

        return $this->askQuestion($question);
    }

    /**
     * @return string
     */
    public function getTmpDirValue()
    {
        return $this->askQuestion(
            new Console\Question\Question('Your wished tmp dir (default /tmp/): ', '/tmp/')
        );
    }
}
