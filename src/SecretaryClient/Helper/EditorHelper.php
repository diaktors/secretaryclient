<?php

namespace SecretaryClient\Helper;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The EditorHelper class interacts with the shell editor and the user.
 */
class EditorHelper extends Helper
{
    private $editor    = 'vi';
    private $tmpFile   = 'secretary_tmp';
    private $tmpFolder = '/tmp/';

    private static $shell;

    /**
     * Constructor.
     *
     * @param string $editor
     * @param string $tmpFolder
     */
    public function __construct($editor = 'vi', $tmpFolder)
    {
        if (!empty($editor)) {
            $this->editor = $editor;
        }
        if (!empty($tmpFolder) && is_dir($tmpFolder)) {
            $this->tmpFolder = $tmpFolder;
        }
    }

    /**
     * Use a system editor to create note content.
     *
     * @param OutputInterface $output
     * @param string $content
     * @param bool $return
     * @return string Note content created by editor
     *
     * @throws \RuntimeException If there is no shell support
     */
    public function useEditor(OutputInterface $output, $content = 'Provide note content here', $return = true)
    {
        file_put_contents($this->tmpFolder . $this->tmpFile, $content);
        $appendCommand = '';
        if ($this->editor == 'vi') {
            $appendCommand = ' < `tty` > `tty`';
        }

        $editorCall = sprintf(
            '%s %s%s %s',
            $this->editor,
            $this->tmpFolder,
            $this->tmpFile,
            $appendCommand
        );

        if (!$this->getShell()) {
            throw new \RuntimeException('No shell support!');
        }
        passthru($editorCall);

        $fileContent = file_get_contents($this->tmpFolder . $this->tmpFile);

        file_put_contents($this->tmpFolder . $this->tmpFile, '');
        unlink($this->tmpFolder . $this->tmpFile);

        if ($return === true) {
            return $fileContent;
        }

        unset($content);
        unset($fileContent);

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'editor';
    }

    /**
     * Returns a valid unix shell.
     *
     * @return string|bool The valid shell name, false in case no valid shell is found
     */
    private function getShell()
    {
        if (null !== self::$shell) {
            return self::$shell;
        }

        self::$shell = false;

        if (file_exists('/usr/bin/env')) {
            // handle other OSs with bash/zsh/ksh/csh if available to hide the answer
            $test = "/usr/bin/env %s -c 'echo OK' 2> /dev/null";
            foreach (array('bash', 'zsh', 'ksh', 'csh') as $sh) {
                if ('OK' === rtrim(shell_exec(sprintf($test, $sh)))) {
                    self::$shell = $sh;
                    break;
                }
            }
        }

        return self::$shell;
    }
}
