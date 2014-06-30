<?php

namespace SecretaryClient\Command;

use SecretaryClient\Helper;
use SecretaryClient\Client;
use Symfony\Component\Console;

class view extends Base
{
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

        $noteId = $input->getArgument('noteId');
        if (empty($noteId) || !is_numeric($noteId)) {
            $output->writeln('<error>Please provide a valid Note ID</error>');
            return;
        }

        $config = $this->getConfiguration();
        /** @var Client\Note $client */
        $client = $this->getClient('note', $config);
        $note = $client->get($noteId, $config['userId']);

        if ($client->hasError()) {
            $output->writeln(sprintf('<error>%s</error>', $client->getError()));
            return;
        }

        /** @var Console\Helper\QuestionHelper $helper */
        $helper = $this->getHelperSet()->get('question');
        $question = new Console\Question\Question('Private Key Passphrase: ');
        $question->setHidden(true)
            ->setHiddenFallback(false)
            ->setValidator(function ($answer) {
                    if (empty($answer)) {
                        throw new \InvalidArgumentException('You need to provide a key passphrase value');
                    }
                    return $answer;
                });
        $passphrase = $helper->ask($input, $output, $question);

        $noteDecrypted = $this->decrypt(
            $note['content'],
            $note['eKey'],
            file_get_contents($config['privateKeyPath']),
            $passphrase
        );

        $question = new Console\Question\ChoiceQuestion(
            'Show note content in editor?',
            array('yes', 'no'),
            1
        );
        $question->setErrorMessage('Answer %s is invalid.');
        $editor = $helper->ask($input, $output, $question);

        if ($editor == 'yes') {
            /** @var Helper\EditorHelper $helper */
            $helper = $this->getHelperSet()->get('editor');
            $content = $helper->useEditor($output, $noteDecrypted);
        } else {
            $output->writeln(sprintf('<info>%s</info>', $note['title']));
            $output->writeln("----------------------------------------\n");
            $output->writeln($noteDecrypted);
            $output->writeln('----------------------------------------');
        }

        unset($passphrase);
        unset($noteDecrypted);

        return;
    }

    /**
     * Decrypt string with private key
     *
     * @param  string $content
     * @param  string $eKey
     * @param  string $key
     * @param  string $passphrase
     * @return string
     * @throws \InvalidArgumentException If content, ekey, key or passphrase is empty
     * @throws \LogicException           If key is not readable as key
     * @throws \LogicException           If encryption errors
     */
    public function decrypt($content, $eKey, $key, $passphrase)
    {
        if (empty($content)) {
            throw new \InvalidArgumentException('Content canot be empty');
        }
        if (empty($eKey)) {
            throw new \InvalidArgumentException('eKey canot be empty');
        }
        if (empty($key)) {
            throw new \InvalidArgumentException('Key canot be empty');
        }
        if (empty($passphrase)) {
            throw new \InvalidArgumentException('Passphrase canot be empty');
        }
        $pk = openssl_pkey_get_private($key, $passphrase);
        if (false === $pk) {
            throw new \LogicException('Key is not readable');
        }
        $content = base64_decode($content);
        $eKey    = base64_decode($eKey);
        $check   = openssl_open($content, $contentDecrypted, $eKey, $pk);
        openssl_free_key($pk);
        if (false === $check) {
            throw new \LogicException(openssl_error_string());
        }
        return unserialize($contentDecrypted);
    }
}
