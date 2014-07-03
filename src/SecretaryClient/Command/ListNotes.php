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
 * ListNotes Command
 */
class ListNotes extends Base
{
    /**
     * Configure listNotes command
     */
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

        $this->input = $input;
        $this->output = $output;

        $search = $input->getOption('search');
        $group = $input->getOption('group');
        $private = $input->getOption('private');
        $page = (int) $input->getOption('page');
        if (empty($page)) {
            $page = 1;
        }

        try {
            /** @var Client\Note $client */
            $client = $this->getClient('note', $this->config);
            if (!empty($search)) {
                $output->writeln('You searched for: ' . $search);
                $notes = $client->searchNotes($search, $page, $group, $private);
            } else {
                $notes = $client->listNotes($page, $group, $private);
            }
            $client->checkForError();
        } catch(Client\Exception $e) {
            $output->writeln($e->getMessage());
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
