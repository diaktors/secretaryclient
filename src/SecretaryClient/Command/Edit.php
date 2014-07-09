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
 * Edit Command
 */
class Edit extends NoteBase
{
    /**
     * Configure create command
     */
    protected function configure()
    {
        $this
            ->setName('edit')
            ->setDescription('Edit a note')
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

        $passphrase = $this->getPassphraseValue();

        /** @var Helper\EditorHelper $editorHelper */
        $editorHelper = $this->getHelperSet()->get('editor');
        $editTitle = $this->getEditValue();
        $title = $note['title'];

        if ($editTitle == 1) {
            $title = $editorHelper->useEditor($output, $title);
            $title = trim($title);
        }

        $editContent= $this->getEditValue('content');
        $contentDecrypted = $this->decryptNote($note, $passphrase);
        $contentEdited = $contentDecrypted;
        if ($editContent == 1) {
            $contentEdited = $editorHelper->useEditor($output, $contentDecrypted);
        }

        if ($title == $note['title'] && $contentDecrypted == $contentEdited) {
            $output->writeln('<info>No change made, no action required.</info>');
            return;
        }

        try {
            if ($note['private'] === true) {
                $note = $this->updatePrivateNote($noteId, $title, $contentEdited);
            } else {
                $note = $this->updateGroupNote($noteId, $title, $contentEdited);
            }
        } catch(Client\Exception $e) {
            $output->writeln($e->getMessage());
            return;
        }

        $output->writeln('Note with ID: ' . $note['id'] . ' was updated.');

        unset($passphrase);
        unset($contentDecrypted);
        unset($contentEdited);

        return;
    }

    /**
     * @param string $kind
     * @return int
     */
    private function getEditValue($kind = 'title')
    {
        $question = new Console\Question\ChoiceQuestion(
            'Edit ' . $kind . ' of note?',
            array('no', 'yes'),
            0
        );
        $question->setErrorMessage('Answer %s is invalid.');
        $continue = $this->askQuestion($question);

        $action = 1;
        if($continue == 'no') {
            $action = 0;
        }

        return $action;
    }

    /**
     * @param int $note
     * @throws \LogicException
     * @return array
     */
    private function getNoteUsers($note)
    {
        /** @var Client\User2Note $user2NoteClient */
        $user2NoteClient = $this->getClient('user2note', $this->config);

        $users = $user2NoteClient->getNoteRecords($note);
        if ($users['count'] == 0) {
            throw new \LogicException('No User2Note records found.');
        }

        $userWithKeys = [];
        foreach ($users['_embedded']['user2note'] as $user) {
            $userWithKeys[$user['userId']] = $this->getUserPublicKey($user['userId']);
        }

        return $userWithKeys;
    }

    /**
     * @param int $id
     * @param string $title
     * @param string $content
     * @return array
     */
    private function updatePrivateNote($id, $title, $content)
    {
        $contentWithKey = $this->cryptService->encryptForSingleKey($content, $this->config['publicKey']);
        unset($content);

        /** @var Client\Note $client */
        $client = $this->getClient('note', $this->config);
        $note = $client->updatePrivateNote(
            $id,
            $title,
            $contentWithKey['content']
        );
        $client->checkForError();

        /** @var Client\User2Note $client */
        $client = $this->getClient('user2note', $this->config);
        $client->updateUser2Note(
            $this->config['userId'],
            $note['id'],
            $contentWithKey['ekey']
        );
        $client->checkForError();

        return $note;
    }



    /**
     * @param int $id
     * @param string $title
     * @param string $content
     * @return array
     */
    private function updateGroupNote($id, $title, $content)
    {
        $userWithKeys = $this->getNoteUsers($id);
        $contentWithKeys = $this->cryptService->encryptForMultipleKeys($content, $userWithKeys);
        unset($content);

        /** @var Client\Note $client */
        $noteClient = $this->getClient('note', $this->config);
        $note = $noteClient->updatePrivateNote(
            $id,
            $title,
            $contentWithKeys['content']
        );
        $noteClient->checkForError();

        $i = 0;
        /** @var Client\User2Note $user2NoteClient */
        $user2NoteClient = $this->getClient('user2note', $this->config);
        foreach ($userWithKeys as $user => $key){
            $user2NoteClient->updateUser2Note(
                $user,
                $note['id'],
                $contentWithKeys['ekeys'][$i]
            );
            $user2NoteClient->checkForError();
            $i++;
        }

        return $note;
    }
}
