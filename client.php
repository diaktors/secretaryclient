#!/usr/bin/env php
<?php

require_once('vendor/autoload.php');

use SecretaryClient\Command;
use SecretaryClient\Helper\EditorHelper;
use SecretaryCrypt\Crypt;
use Symfony\Component\Console\Application;

$editorHelper = new EditorHelper('vi', '/tmp/');
$cryptService = new Crypt();

$application = new Application();
$application->add(new Command\Configuration());
$application->add(new Command\Create($cryptService));
$application->add(new Command\ListNotes());
$application->add(new Command\View($cryptService));
$application->getHelperSet()->set($editorHelper, 'editor');
$application->run();