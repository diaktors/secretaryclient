<?php

namespace SecretaryClient\Command;

use SecretaryClient\Helper;
use SecretaryClient\Client;
use SecretaryCrypt\Crypt;
use Symfony\Component\Console;

class Create extends Base
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
            ->setName('create')
            ->setDescription('Create a new note')
            ->addOption(
               'file',
               null,
                Console\Input\InputOption::VALUE_OPTIONAL,
               'If set, file (path) is used to fetch content of note'
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

        //$file = $input->getOption('file');
        //$output->writeln($file);

        /** @var Console\Helper\QuestionHelper $helper */
        $helper = $this->getHelperSet()->get('question');

        $question = new Console\Question\ChoiceQuestion(
            'Private or Group note?',
            array('private', 'group'),
            0
        );
        $question->setErrorMessage('Answer %s is invalid.');
        $kind = $helper->ask($input, $output, $question);

        $private = 1;
        if($kind == 'group') {
            $private = 0;
        }

        $question = new Console\Question\Question('Title of note: ');
        $question->setValidator(function ($answer) {
            if (empty($answer)) {
                throw new \InvalidArgumentException('You need to provide a title value');
            }
            return $answer;
        });
        $title = $helper->ask($input, $output, $question);

        /** @var Helper\EditorHelper $helper */
        $helper = $this->getHelperSet()->get('editor');
        $content = $helper->useEditor($output);

        $contentWithKey = $this->cryptService->encryptForSingleKey($content, $this->config['publicKey']);
        unset($content);

        $config = $this->getConfiguration();
        /** @var Client\Note $client */
        $client = $this->getClient('note', $config);
        $note = $client->create(
            $private,
            $title,
            $contentWithKey['content'],
            $contentWithKey['ekey'],
            $config['userId']
        );

        $output->writeln('Note with ID: ' . $note['id'] . ' was created.');

        return;
    }
}
