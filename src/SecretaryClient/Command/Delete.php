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
use Symfony\Component\Console;

/**
 * Delete Command
 */
class Delete extends NoteBase
{
    /**
     * Configure delete command
     */
    protected function configure()
    {
        $this
            ->setName('delete')
            ->setDescription('Delete a note')
            ->addArgument(
                'noteId',
                Console\Input\InputArgument::REQUIRED,
                'The noteId of the note you want to edit.'
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
            $output->writeln('<error>Please provide a valid Note ID.</error>');
            return;
        }

        try {
            $note = $this->getNote($noteId);
        } catch(Client\Exception $e) {
            $output->writeln($e->getMessage());
            return;
        }

        if ($note['writePermission'] === false) {
            $output->writeln('<error>You do not have write permission for this note.</error>');
            return;
        }

        $deleteConfirmation = $this->getDeleteConfirmationValue($note);
        if ($deleteConfirmation === false) {
            $output->writeln('<info>Note was not deleted.</info>');
            return;
        }

        /** @var Client\Note $noteClient */
        $noteClient = $this->getClient('note', $this->config);
        try {
            $noteClient->delete($noteId);
            $noteClient->checkForError();
        } catch(Client\Exception $e) {
            $output->writeln($e->getMessage());
            return;
        }

        $message = sprintf(
            'Note with ID %d and title "%s" was deleted',
            $note['id'],
            $note['title']
        );
        $output->writeln('<info>' . $message . '</info>');

        unset($note);

        return;
    }

    /**
     * @param array $note
     * @return bool
     */
    private function getDeleteConfirmationValue(array $note)
    {
        $questionText = sprintf(
            'Delete note with ID %d and title "%s"?: ',
            $note['id'],
            $note['title']
        );
        $question = new Console\Question\ConfirmationQuestion(
            $questionText,
            false
        );

        return $this->askQuestion($question);
    }
}
