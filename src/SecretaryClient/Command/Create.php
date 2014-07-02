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

        $config = $this->getConfiguration();

        if ($private == 1) {
            $contentWithKey = $this->cryptService->encryptForSingleKey($content, $this->config['publicKey']);
            unset($content);

            /** @var Client\Note $client */
            $client = $this->getClient('note', $config);

            $note = $client->createPrivateNote(
                $private,
                $title,
                $contentWithKey['content'],
                $contentWithKey['ekey']
            );

            if ($client->hasError()) {
                $output->writeln(sprintf('<error>%s</error>', $client->getError()));
                return;
            }
        }

        elseif ($private == 0) {

            // ask for group

            /** @var Client\Group $groupClient */
            $groupClient = $this->getClient('group', $config);
            $groupPaginator = $groupClient->getUserGroups();

            if ($groupClient->hasError()) {
                $output->writeln(sprintf('<error>%s</error>', $groupClient->getError()));
                return;
            }

            $groups = $this->extractGroupNames($groupPaginator['_embedded']['group']);
            $groupUsers = $this->extractGroupUsers($groupPaginator['_embedded']['group']);

            /** @var Console\Helper\QuestionHelper $helper */
            $helper = $this->getHelperSet()->get('question');
            $question = new Console\Question\ChoiceQuestion('Select a group:', $groups);
            $selectedGroupName = $helper->ask($input, $output, $question);
            $flippedGroups = array_flip($groups);
            $selectedGroup = $flippedGroups[$selectedGroupName];

            // ask for user of group

            /** @var Client\User $userClient */
            $userClient = $this->getClient('user', $config);
            $groupUsersData = $userClient->getUsers($groupUsers[$selectedGroup]);

            if ($userClient->hasError()) {
                $output->writeln(sprintf('<error>%s</error>', $userClient->getError()));
                return;
            }

            $users = $this->extractUserNames($groupUsersData);
            unset($users[$config['userId']]);
            $question = new Console\Question\ChoiceQuestion('Select a user:', $users);
            $selectedUserName = $helper->ask($input, $output, $question);
            $flippedUsers = array_flip($users);
            $selectedUser = $flippedUsers[$selectedUserName];

            /** @var Client\Key $keyClient */
            $keyClient = $this->getClient('key', $config);
            $selectedUserKey = $keyClient->getById($selectedUser);

            if ($userClient->hasError()) {
                $output->writeln(sprintf('<error>%s</error>', $userClient->getError()));
                return;
            }

            $cryptUserKeys = [
                $selectedUser => $selectedUserKey,
                $config['userId'] => $config['publicKey'],
            ];

            $contentWithKey = $this->cryptService->encryptForMultipleKeys($content, $cryptUserKeys);
            unset($content);

            /** @var Client\Note $client */
            $client = $this->getClient('note', $config);

            $note = $client->createGroupNote(
                $private,
                $title,
                $contentWithKey['content'],
                $selectedGroup,
                $contentWithKey,
                array_keys($cryptUserKeys)
            );

            if ($client->hasError()) {
                $output->writeln(sprintf('<error>%s</error>', $client->getError()));
                return;
            }
        }

        $output->writeln('Note with ID: ' . $note['id'] . ' was created.');

        return;
    }

    /**
     * @param array $collection
     * @return array
     */
    private function extractGroupNames(array $collection)
    {
        $groups = [];
        foreach ($collection as $group) {
            $groups[$group['id']] = $group['name'];
        }

        return $groups;
    }

    /**
     * @param array $collection
     * @return array
     */
    private function extractGroupUsers(array $collection)
    {
        $groupUsers = [];
        foreach ($collection as $group) {
            $groupUsers[$group['id']] = $group['users'];
        }

        return $groupUsers;
    }

    /**
     * @param array $collection
     * @return array
     */
    private function extractUserNames(array $collection)
    {
        $users = [];
        foreach ($collection as $user) {
            $users[$user['id']] = $user['email'];
        }

        return $users;
    }
}
