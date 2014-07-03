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
 * @category Helper
 * @package  SecretaryClient
 * @author   Michael Scholl <michael@wesrc.com>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://github.com/wesrc/secretary
 */

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

        return '';
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
