<?php

namespace SecretaryClient\Command;

use SecretaryClient\Helper;
use SecretaryClient\Client;
use SecretaryCrypt\Crypt;
use Symfony\Component\Console;

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

        $noteDecrypted = $this->cryptService->decrypt(
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
}
