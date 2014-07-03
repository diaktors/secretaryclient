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

use SecretaryClient\Helper;
use SecretaryClient\Client;
use SecretaryCrypt\Crypt;
use Symfony\Component\Console;

/**
 * View Command
 */
class View extends Base
{
    /**
     * @var Crypt
     */
    private $cryptService;

    /**
     * @param Crypt $cryptService
     */
    public function __construct(Crypt $cryptService)
    {
        $this->cryptService = $cryptService;
        parent::__construct();
    }

    /**
     * Configure view command
     */
    protected function configure()
    {
        $this
            ->setName('view')
            ->setDescription('View note content')
            ->addArgument(
               'noteId',
                Console\Input\InputArgument::REQUIRED,
               'The noteId of the note you want to view.'
            )
        ;
    }

    /**
     * @param Console\Input\InputInterface $input
     * @param Console\Output\OutputInterface $output
     * @return void
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $this->checkConfiguration($output);

        $this->input = $input;
        $this->output = $output;

        $noteId = $input->getArgument('noteId');
        if (empty($noteId) || !is_numeric($noteId)) {
            $output->writeln('<error>Please provide a valid Note ID</error>');
            return;
        }

        try {
            $note = $this->getNote($noteId);
        } catch(Client\Exception $e) {
            $output->writeln($e->getMessage());
            return;
        }

        $passphrase = $this->getPassphraseValue();
        $noteDecrypted = $this->decryptNote($note, $passphrase);
        $viewInEditor = $this->getViewInEditorValue();

        $this->writeNote($viewInEditor, $note['title'], $noteDecrypted);

        unset($passphrase);
        unset($noteDecrypted);

        return;
    }

    /**
     * @param array $note
     * @param string $passphrase
     * @return string
     */
    private function decryptNote(array $note, $passphrase)
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
    private function getNote($noteId)
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
    private function getPassphraseValue()
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
     * @return array
     */
    private function getViewInEditorValue()
    {
        $question = new Console\Question\ChoiceQuestion(
            'Show note content in editor?',
            array('yes', 'no'),
            1
        );
        $question->setErrorMessage('Answer %s is invalid.');

        return $this->askQuestion($question);
    }

    /**
     * @param string $viewInEditor
     * @param string $title
     * @param string $content
     */
    private function writeNote($viewInEditor, $title, $content)
    {
        if ($viewInEditor == 'yes') {
            /** @var Helper\EditorHelper $helper */
            $helper = $this->getHelperSet()->get('editor');
            $content = $helper->useEditor($this->output, $content);
        } else {
            $this->output->writeln(sprintf('<info>%s</info>', $title));
            $this->output->writeln("----------------------------------------\n");
            $this->output->writeln($content);
            $this->output->writeln('----------------------------------------');
        }
        unset($content);
    }
}
