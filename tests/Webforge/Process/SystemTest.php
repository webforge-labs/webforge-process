<?php

namespace Webforge\Process;

use Webforge\Common\System\Util as SystemUtil;
use Mockery as m;
use Webforge\Common\System\ExecutableFinder;

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
