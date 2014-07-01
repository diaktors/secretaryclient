<?php

namespace SecretaryClient\Command;

use SecretaryClient\Client;
use Symfony\Component\Console;

abstract class Base extends Console\Command\Command
{
    /**
     * @var null|array
     */
    protected $config = null;

    /**
     * @var array
     */
    protected $availableClients = ['note', 'user', 'key', 'group'];

    /**
     * Check if configuration is already done - or trigger it
     *
     * @param Console\Output\OutputInterface $output
     */
    protected function checkConfiguration(Console\Output\OutputInterface $output)
    {
        /** @var Configuration $configureCommand */
        $configureCommand = $this->getApplication()->get('configure');

        if (file_exists($configureCommand->getConfigurationFile())) {
            $this->getConfiguration();
            return;
        }

        $arguments = array(
            'command' => 'configure',
        );

        $input = new Console\Input\ArrayInput($arguments);
        $configureCommand->run($input, $output);

        $this->getConfiguration();

        return;
    }

    /**
     * @param string $kind
     * @param array $config
     * @return Client\Base
     * @throws \LogicException If given client kind is not available
     */
    protected function getClient($kind = 'user', array $config)
    {
        if (!in_array($kind, $this->availableClients)) {
            throw new \LogicException('Given client kind is not available');
        }
        $client = "SecretaryClient\\Client\\" . ucfirst($kind);
        return new $client(
            $config['apiUrl'],
            $config['username'],
            $config['password']
        );
    }

    /**
     * @return array
     */
    protected function getConfiguration()
    {
        if ($this->config !== null) {
            return $this->config;
        }

        /** @var Configuration $configureCommand */
        $configureCommand = $this->getApplication()->get('configure');

        $configurationJson = file_get_contents($configureCommand->getConfigurationFile());
        $this->config = json_decode($configurationJson, true);

        return $this->config;
    }
}
