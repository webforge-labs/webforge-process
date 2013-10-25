<?php

require dirname(__DIR__).DIRECTORY_SEPARATOR.'bootstrap.php';

$compiler = new Webforge\Process\Phar\Compiler();
$compiler->compile(__DIR__.DIRECTORY_SEPARATOR.'webforge-process.phar', 'webforge-process.phar');