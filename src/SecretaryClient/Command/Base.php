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
 * Command Base class
 */
abstract class Base extends Console\Command\Command
{
    /**
     * @var null|array
     */
    protected $config = null;

    /**
     * @var Console\Input\InputInterface
     */
    protected $input;

    /**
     * @var Console\Output\OutputInterface
     */
    protected $output;

    /**
     * @var array
     */
    protected $availableClients = ['note', 'user', 'key', 'group', 'user2note'];

    /**
     * @param Console\Question\Question $question
     * @return string
     */
    protected function askQuestion(Console\Question\Question $question)
    {
        /** @var Console\Helper\QuestionHelper $helper */
        $helper = $this->getHelperSet()->get('question');

        return $helper->ask($this->input, $this->output, $question);
    }

    /**
     * Check if configuration is already done - or trigger it
     *
     * @param Console\Output\OutputInterface $output
     */
    protected function checkConfiguration(Console\Output\OutputInterface $output)
    {
        /** @var Configuration $configureCommand */
        $configureCommand = $this->getApplication()->get('configure');

        if (file_exists($configureCommand->getConfigurationFile())) {
            $this->getConfiguration();
            return;
        }

        $arguments = array(
            'command' => 'configure',
        );

        $input = new Console\Input\ArrayInput($arguments);
        $configureCommand->run($input, $output);

        $this->getConfiguration();

        return;
    }

    /**
     * @param string $kind
     * @param array $config
     * @return Client\Base
     * @throws \LogicException If given client kind is not available
     */
    protected function getClient($kind = 'user', array $config)
    {
        if (!in_array($kind, $this->availableClients)) {
            throw new \LogicException('Given client kind is not available');
        }
        $kind = ucfirst($kind);
        if ($kind == 'user2note') {
            $kind = 'User2Note';
        }
        $client = "SecretaryClient\\Client\\" . $kind;
        return new $client(
            $config['apiUrl'],
            $config['username'],
            $config['password']
        );
    }

    /**
     * @return array
     */
    protected function getConfiguration()
    {
        if ($this->config !== null) {
            return $this->config;
        }

        /** @var Configuration $configureCommand */
        $configureCommand = $this->getApplication()->get('configure');

        $configurationJson = file_get_contents($configureCommand->getConfigurationFile());
        $this->config = json_decode($configurationJson, true);

        return $this->config;
    }
}
