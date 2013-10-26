<?php

namespace Webforge\Process;

use Webforge\Common\System\System as SystemInterface;
use Symfony\Component\Process\Process As SymfonyProcess;
use Webforge\Common\System\Util as SystemUtil;
use Webforge\Common\System\Container as SystemContainer;
use Webforge\Common\System\Dir;

class System implements SystemInterface {

  protected $os;

  protected $container;

  protected $executables;

  protected $workingDirectory = NULL;

  public function __construct(SystemContainer $container) {
    $this->os = SystemUtil::isWindows() ? self::WINDOWS : self::UNIX;
    $this->container = $container;
    $this->executables = $this->container->getExecutableFinder();
  }

  /**
   * @inherit-doc
   */
  public function exec($commandline, $options = NULL, $runCallback = NULL) {
    if ($options instanceof \Closure) {
      $runCallback = $options;
      $options = array();
    } else if(!$runCallback) {
      $runCallback = function() {};
    }

    $process = $this->process($commandline, $options);

    return $process->run($runCallback);
  }

  /**
   * 
   * the implementation of the process command is not yet fully operational
   * 
   * the parameters supported are: 
   *   - none!
   * 
   * @return Symfony\Component\Process\Process
   */
  public function process($commandline, $options = NULL) {
    $process = new SymfonyProcess(
      $commandline,
      $this->getWorkingDirectory(), // || options['cwd']
      $env = NULL,
      $stdin = NULL,
      $timeout = $this->getDefaultTimeout(),
      $opt = array()
    );

    return $process;
  }

  /**
   * @return Webforge\Process\ProcessBuilder
   */
  public function buildProcess() {
    $builder = ProcessBuilder::create(array());

    if ($this->getWorkingDirectory() != NULL) {
      $builder->setWorkingDirectory($this->getWorkingDirectory());
    }

    return $builder;
  }

  /**
   * @return Webforge\Process\ProcessBuilder
   */
  public function buildPHPProcess() {
    return $this->buildProcess()->setPrefix($this->which('php'));
  }

  /**
   * @inherit-doc
   */
  public function passthru($commandline, $options = NULL) {
    if ($this->getWorkingDirectory() != NULL) {
      $commandline = 'cd '.$this->getWorkingDirectory()->getQuotedString().' && '.$commandline;
    }

    $retvar = NULL;
    passthru($commandline, $retvar);
    return $retvar;
  }

  /**
   * Returns the file to a executable if findable
   * 
   * @return Webforge\Common\System\File
   */
  public function which($name) {
    return $this->executables->getExecutable($name);
  }

  /**
   * @return string|NULL if NULL is returned the current is used
   */
  public function getWorkingDirectory() {
    return $this->workingDirectory;
  }

  public function setWorkingDirectory(Dir $directory) {
    $this->workingDirectory = $directory;
    return $this;
  }

  public function getDefaultTimeout() {
    return 60;
  }

  public function getOperatingSystem() {
    return $this->os;
  }
}
