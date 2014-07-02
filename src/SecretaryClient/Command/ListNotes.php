<?php

namespace SecretaryClient\Command;

use SecretaryClient\Helper;
use SecretaryClient\Client;
use Symfony\Component\Console;

class ListNotes extends Base
{
    protected function configure()
    {
        $this
            ->setName('listNotes')
            ->setDescription('List notes inside table view')
            ->addOption(
               'search',
               null,
                Console\Input\InputOption::VALUE_OPTIONAL,
               'Search for matching note titles.'
            )
            ->addOption(
                'page',
                null,
                Console\Input\InputOption::VALUE_OPTIONAL,
                'Page of view results.'
            )
            ->addOption(
                'group',
                null,
                Console\Input\InputOption::VALUE_OPTIONAL,
                'Filter group only results.'
            )
            ->addOption(
                'private',
                null,
                Console\Input\InputOption::VALUE_OPTIONAL,
                'Filter private only results.'
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

        $search = $input->getOption('search');
        $group = $input->getOption('group');
        $private = $input->getOption('private');
        $page = (int) $input->getOption('page');
        if (empty($page)) {
            $page = 1;
        }

        $config = $this->getConfiguration();
        /** @var Client\Note $client */
        $client = $this->getClient('note', $config);
        if (!empty($search)) {
            $output->writeln('You searched for: ' . $search);
            $notes = $client->searchNotes($search, $page, $group, $private);
        } else {
            $notes = $client->listNotes($page, $group, $private);
        }

        if ($client->hasError()) {
            $output->writeln(sprintf('<error>%s</error>', $client->getError()));
            return;
        }

        if ($notes['count'] == 0) {
            $output->writeln('No note records given.');
            return;
        }

        $this->writeNotesTable($output, $notes);

        $this->writeInfoFooter($output, $notes, $page);

        return;
    }

    /**
     * @param Console\Output\OutputInterface $output
     * @param array $notes
     */
    private function writeNotesTable(Console\Output\OutputInterface $output, array $notes)
    {
        $table = new Console\Helper\Table($output);
        $table->setStyle('borderless');
        $table->setHeaders(['ID', 'Private', 'Title', 'Group (ID)','created', 'updated']);

        foreach ($notes['_embedded']['note'] as $note) {
            $group = '';
            if (!empty($note['groupId'])) {
                $group = sprintf(
                    '%s (%d)',
                    $note['groupName'],
                    $note['groupId']
                );
            }
            $table->addRow([
                $note['id'],
                $note['private'] ? 1:0,
                $note['title'],
                $group,
                $note['dateCreated']['date'],
                $note['dateUpdated']['date']
            ]);
        }

        $table->render();
    }

    /**
     * @param Console\Output\OutputInterface $output
     * @param array $notes
     * @param int $page
     */
    private function writeInfoFooter(Console\Output\OutputInterface $output, array $notes, $page)
    {
        $output->writeln(sprintf(
            'Page: %d of %d | Items per page: %d | Notes Total: %d | Page Total: %d',
            $page,
            $notes['page_count'],
            $notes['page_size'],
            $notes['total_items'],
            $notes['count']
        ));
    }
}
