<?php

namespace Webforge\Process;

use Webforge\Common\System\System as SystemInterface;
use Symfony\Component\Process\Process As SymfonyProcess;
use Webforge\Common\System\Util as SystemUtil;
use Webforge\Common\System\Container as SystemContainer;

class System implements SystemInterface {

  protected $os;

  protected $container;

  protected $executables;

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
      $this->getCurrentWorkDirectory(), // || options['cwd']
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
    return ProcessBuilder::create(array());
  }

  /**
   * @return Webforge\Process\ProcessBuilder
   */
  public function buildPHPProcess() {
    return ProcessBuilder::create(array(
      $this->executables->getExecutable('php')
    ));
  }

  /**
   * @inherit-doc
   */
  public function passthru($commandline, $options = NULL) {
    return passthru($commandline);
  }

  /**
   * @return string|NULL if NULL is returned the current is used
   */
  public function getCurrentWorkDirectory() {
    return NULL;
  }

  public function getDefaultTimeout() {
    return 60;
  }

  public function getOperatingSystem() {
    return $this->os;
  }
}
