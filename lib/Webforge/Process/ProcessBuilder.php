<?php

namespace Webforge\Process;

use Symfony\Component\Process\ProcessBuilder as SymfonyProcessBuilder;

class ProcessBuilder extends SymfonyProcessBuilder {

  public function __construct(Array $arguments = array()) {
    parent::__construct($arguments);

    $inherits = array(      
      'PATH','SystemRoot','LOCALAPPDATA','SystemDrive','SSH_AUTH_SOCK','CommonProgramFiles',
      'APPDATA','COMPUTERNAME','TEMP','TMP','USERNAME',
      'PHPRC', 'PHP_PEAR_BIN_DIR', 'PHP_PEAR_PHP_BIN', 'PSC_CMS',
      'XDEBUG_CONFIG', 'WEBFORGE'
    );

    foreach ($inherits as $inherit) {
      $this->setEnv($inherit, getenv($inherit));
    }
    $this->setEnv('USERPROFILE', getenv('HOME'));
    
  }

  public function addArguments(Array $arguments) {
    foreach ($arguments as $arg) {
      $this->add($arg);
    }
    return $this;
  }
}
