<?php

namespace Webforge\Process;

use Webforge\Common\System\Util as SystemUtil;
use Mockery as m;
use Webforge\Common\System\ExecutableFinder;
use Webforge\Common\System\Dir;

/**
 * 
 * most of the stuff is not needed to be tested because we relie on symfony process to handle the details
 * 
 * but we'll do some simple acceptance tests anyway
 */
class SystemTest extends \Webforge\Code\Test\Base {
  
  public function setUp() {
    $this->chainClass = 'Webforge\\Process\\System';
    parent::setUp();

    $this->systemContainer = m::mock('Webforge\Common\System\Container');
    $this->systemContainer->shouldReceive('getExecutableFinder')->byDefault()->andReturn(new ExecutableFinder(array()));

    $this->system = new System($this->systemContainer);

    if (SystemUtil::isWindows()) {
      $this->ls = 'dir';
    } else {
      $this->ls = 'ls -la';
    }
  }

  public function testInterfaceImplementation() {
    $this->assertInstanceOf('Webforge\Common\System\System', $this->system);
  }

  public function testExecReturnsTheCorrectExitCode() {
    $exitCode = $this->system->exec($this->ls);

    $this->assertSame(0, $exitCode);
  }

  public function testExecReturnsTheCorrectExitCodeWithOptions() {
    $exitCode = $this->system->exec($this->ls, array());

    $this->assertSame(0, $exitCode);
  }

  public function testExecPassesTheRunCallbackToProcess() {
    $output = '';

    $exitCode = $this->system->exec($this->ls, function($type, $out) use(&$output) {
      $output .= $out;
    });

    $this->assertNotEmpty($output);
  }

  public function testExecPassesTheRunCallbackToProcessWithOptions() {
    $output = '';

    $exitCode = $this->system->exec($this->ls, array(), function($type, $out) use(&$output) {
      $output .= $out;
    });

    $this->assertNotEmpty($output);
  }

  public function testWhichReturnsTheLsFile() {
    // works only on cygwin installed windows hosts (maybe)
    $ls = $this->system->which('ls');
    $this->assertInstanceOf('Webforge\Common\System\File', $ls);
  }

  public function testChangesWorkingDirectoryForProcess() {
    $this->system->setWorkingDirectory($this->getTestDirectory('ls-folder/'));

    $lsOut = NULL;
    $this->system->buildProcess()->setPrefix($this->system->which('ls'))->getProcess()->run(function($type, $out) use (&$lsOut) {
      $lsOut .= $out;
    });

    $this->assertLsOutput($lsOut, 'I assume the working directory was not changed to ls-folder');
  }

  public function testChangesWorkingDirectoryForPassthru() {
    // lets get dirty
    $this->system->setWorkingDirectory($wc = $this->getTestDirectory('ls-folder/'));

    ob_start();
    $this->system->passthru($this->system->which('ls'));
    $lsOut = ob_get_contents();
    ob_end_clean();
    

    $this->assertLsOutput($lsOut, 'I assume the wc was not changed to ls-folder');
  }

  protected function assertLsOutput($lsOut, $msg) {
    $files = array('one.txt', 'two.txt', 'three.txt');
    $lsFiles = array_filter(explode("\n", $lsOut));

    $this->assertArrayEquals(
      $files,
      $lsFiles,
      $msg
    );
  }

  public function testChangesWorkingDirectoryForBuildPHPPRocess() {
    $this->system->setWorkingDirectory($wc = $this->getTestDirectory('ls-folder/'));

    $process = $this->system->buildPHPProcess()->add('-r')->add('print getcwd();')->getProcess();
    $process->run();

    $this->assertEquals(
      $wc->getPath(Dir::WITHOUT_TRAILINGSLASH),
      trim($process->getOutput())
    );
  }

  public function testProcessWillReturnASymfonyProcess() {
    $this->assertInstanceOf('Symfony\Component\Process\Process', $this->system->process($this->ls));
  }

  public function testBuildProcessReturnsAProcessBuilder() {
    $this->assertInstanceOf('Webforge\Process\ProcessBuilder', $this->system->buildProcess());
  }

  public function testBuildPHPProcessReturnsAProcessBuilder_Preconfigured() {
    $this->assertInstanceOf('Webforge\Process\ProcessBuilder', $builder = $this->system->buildPHPProcess());

    $process = $builder->add('-v')->getProcess();

    $this->assertContains('php', $process->getCommandLine(), 'i expect to find php as an executable in the commandline');

    $this->assertSame(0, $process->run(), $process->getErrorOutput());
    $this->assertStringStartsWith('PHP '.PHP_VERSION, $process->getOutput(), 'little more acceptance here');
  }
}
