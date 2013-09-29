<?php

namespace Webforge\Process;

use Symfony\Component\Process\ProcessBuilder as SymfonyProcessBuilder;
use Webforge\Common\JS\JSONConverter;
use Webforge\Common\System\Util as SystemUtil;

class ProcessBuilderTest extends \Webforge\Code\Test\Base {
  
  public function setUp() {
    $this->chainClass = 'Webforge\\Process\\ProcessBuilder';
    parent::setUp();

    $this->echoBat = $this->getPackageDir('bin/')->getFile('echo.bat');
    $this->echoSh = $this->getPackageDir('bin/')->getFile('echo.sh');
  }

  protected function build() {
    $builder = new ProcessBuilder();

    return $builder;
  }

  /**
   * @dataProvider provideEchoArguments
   */
  public function testProcessBuilderShellEscapingWithAShellDirector(Array $expectedArguments, Array $arguments, $os) {
    if (SystemUtil::isWindows() && $os === SystemUtil::WINDOWS) {
      $bin = $this->echoBat;
    } elseif (!SystemUtil::isWindows() && $os === SystemUtil::UNIX) {
      $bin = $this->echoSh;
    } else {
      return $this->markTestSkipped('Wrong platform for this test');
    }

    $builder = $this->build()->setPrefix($bin);

    $process = $this->configureBuilderProcess($builder, $arguments);
    $this->assertEchoProcess($process, $expectedArguments);
  }

  /**
   * @dataProvider provideEchoArguments
   */
  public function testProcessBuilderShellEscapingWithPHP(Array $expectedArguments, Array $arguments, $os) {
    if (SystemUtil::isWindows() && $os === SystemUtil::WINDOWS || !SystemUtil::isWindows() && $os === SystemUtil::UNIX) {
      $builder = $this->build()
         ->setPrefix('php')
         ->add('-f')
         ->add($this->getPackageDir('bin/')->getFile('echo.php'))
         ->add('--');

      $process = $this->configureBuilderProcess($builder, $arguments);
      $this->assertEchoProcess($process, $expectedArguments);
    }
  }


  protected function configureBuilderProcess($builder, $arguments) {
    $builder->setEnv('DEFINED_VAR', 'this is an defined env value');

    foreach ($arguments as $arg) {
      $builder->add($arg);
    }

    $process = $builder->getProcess();
    $process->setEnhanceWindowsCompatibility(TRUE);

    return $process;
  }

  protected function assertEchoProcess($process, $expectedArguments) {
    $this->assertEquals(0, $process->run(), "Error: Process did not run correctly. It was run:\n".$process->getCommandLine()."\nExitCode 0 was expected\nOutput is:\n".$process->getOutput()."\n".$process->getErrorOutput());
    $this->assertEquals($expectedArguments, JSONConverter::create()->parse($process->getOutput()), "Command Called was:\n".$process->getCommandLine());
  }
  
  public static function provideEchoArguments() {
    $ostests = array();

    $ostests[] = Array(
      array('myargument'), array('myargument')
    );
    
    $ostests[] = Array(
      array('my"argument'), array('my"argument')
    );
    
    $ostests[] = Array(
      array("my'argument"), array("my'argument")
    );

    $ostests[] = Array(
      array('%h%d%Y'), array('%h%d%Y')
    );

    /* Symfony Bug?
    $ostests[] = Array(
      array('--format=%h%d%Y'), array('--format=%h%d%Y')
    );

    $ostests[] = Array(
      array('--format="%h%d%Y"'), array('--format="%h%d%Y"')
    );
    */

    $tests = array();
    foreach (array(SystemUtil::WINDOWS, SystemUtil::UNIX) as $os) {
      foreach ($ostests as $testArgs) {
        $tests[] = array_merge($testArgs, array($os));
      }
    }

    // the application itself has to make sure that the actual arguments are escaped correctly FOR THE CURRENT PLATFORM; when in --arg="value" format. If value has an \ at the end it needs to be escaped
    $tests[] = Array(
      array('--paths="something\with\trailing\\"'), array('--paths="something\with\trailing\\\\"'), SystemUtil::WINDOWS
    );

    // the application itself has to make sure that the actual arguments are escaped correctly FOR THE CURRENT PLATFORM; when in --arg="value" format. If value has an \ at the end it needs to be escaped
    $tests[] = Array(
      array('--paths="something\with\trailing"'), array('--paths="something\with\trailing"'), SystemUtil::UNIX
    );

    return $tests;

    /*
    // Bug in Symfony? How to escape & here?
    $tests[] = Array(
      array('--style="used&dirty"'), array('--style="used&dirty"'), $os
    );

    // & standalone is always treated as "command concatenation"
    $tests[] = Array(
      array('alone&dirty'), array('alone&dirty'), $os
    );

    // Specification: should it be replaced ? or not?
    $tests[] = Array(
      array('this is an defined env value'), array('%DEFINED_VAR%'), $os
    );

    $tests[] = Array(
      array('--env-vars="this is an defined env value"'), array('--env-vars="%DEFINED_VAR%"'), $os
    );

    return $tests;
    */
  }
}
