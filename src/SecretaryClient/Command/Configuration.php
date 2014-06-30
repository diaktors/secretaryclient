<?php

namespace SecretaryClient\Command;

use SecretaryClient\Client;
use Symfony\Component\Console;

class Configuration extends Base
{
    private $configurationFile = 'client_configuration.php';

    public function getConfigurationFile()
    {
        return $this->configurationFile;
    }

    protected function configure()
    {
        $this
            ->setName('configure')
            ->setDescription('Configure SecretaryClient and save configuration for later usage.')
        ;
    }

    /**
     * @param Console\Input\InputInterface $input
     * @param Console\Output\OutputInterface $output
     * @return int|null|void
     *
     * @throws \InvalidArgumentException If user input is missing
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        if (file_exists($this->configurationFile)) {
            $output->writeln('You already configured your SecretaryClient.');
            return;
        }

        $output->writeln("Configuration of SecretaryClient is missing - please provide your settings.\n");

        /** @var Console\Helper\QuestionHelper $helper */
        $helper = $this->getHelperSet()->get('question');

        $question = new Console\Question\Question('Username: ');
        $question->setValidator(function ($answer) {
            if (empty($answer)) {
                throw new \InvalidArgumentException('You need to provide a password value');
            }
            return $answer;
        });
        $username = $helper->ask($input, $output, $question);

        $question = new Console\Question\Question('Password: ');
        $question->setHidden(true)
            ->setHiddenFallback(false)
            ->setValidator(function ($answer) {
                if (empty($answer)) {
                    throw new \InvalidArgumentException('You need to provide a password value');
                }
                return $answer;
            });
        $password = $helper->ask($input, $output, $question);

        $question = new Console\Question\Question('Your wished note editor (default vi): ', 'vi');
        $editor = $helper->ask($input, $output, $question);

        $question = new Console\Question\Question('Your wished tmp dir (default /tmp/): ', '/tmp/');
        $tmpDir = $helper->ask($input, $output, $question);

        $question = new Console\Question\Question('Your private key file path: ', '/Users/mischosch/Desktop/keys/mykey.pem');
        $privateKeyPath = $helper->ask($input, $output, $question);

        $question = new Console\Question\Question('Your secretary url: ', 'http://secretaryapi.dev:8080/');
        $apiUrl = $helper->ask($input, $output, $question);

        $output->writeln("\nGiven username: " . $username);
        $output->writeln('Given editor: ' . $editor);
        $output->writeln('Given tmp dir: ' . $tmpDir);
        $output->writeln('Given private key file path: ' . $privateKeyPath);
        $output->writeln('Given SecretaryApi url: ' . $apiUrl);

        $configuration = array(
            'username' => $username,
            'password' => $password,
            'editor' => $editor,
            'tmpDir' => $tmpDir,
            'privateKeyPath' => $privateKeyPath,
            'apiUrl' => $apiUrl,
        );

        /** @var Client\User $client */
        $client = $this->getClient('user', $configuration);
        $user = $client->getByMail($username);
        $configuration['userId'] = $user['id'];

        /** @var Client\Key $client */
        $client = $this->getClient('key', $configuration);
        $pubKey = $client->getById($user['id']);
        $configuration['publicKey'] = $pubKey;

        file_put_contents($this->configurationFile, json_encode($configuration, JSON_PRETTY_PRINT));

        $this->getConfiguration();

        $output->writeln("\nYou successfully configured your SecretaryClient. You can now start using it.\n");

        return;
    }
}
