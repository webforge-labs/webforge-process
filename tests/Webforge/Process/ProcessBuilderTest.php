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
    //$builder = new ProcessBuilder($bin);

    $builder = new SymfonyProcessBuilder();
    //$builder->setPrefix((string) $bin);

    $inherits = array(
      'PATH','SystemRoot','LOCALAPPDATA','SystemDrive','SSH_AUTH_SOCK','CommonProgramFiles',
      'APPDATA','COMPUTERNAME','TEMP','TMP','USERNAME',
      'PHPRC', 'PHP_PEAR_BIN_DIR', 'PHP_PEAR_PHP_BIN', 'PSC_CMS',
      'XDEBUG_CONFIG', 'WEBFORGE'
    );

    foreach ($inherits as $inherit) {
      $builder->setEnv($inherit, getenv($inherit));
    }
    $builder->setEnv('USERPROFILE', getenv('HOME'));

    return $builder;
  }

  /**
   * @dataProvider provideEchoArguments
   */
  public function testProcessBuilderShellEscaping(Array $expectedArguments, Array $arguments, $os, $runWithPHP) {
    if ($runWithPHP) {
      $builder = $this->build()
         ->setPrefix('php')
         ->add('-f')
         ->add($this->getPackageDir('bin/')->getFile('echo.php'))
         ->add('--');
     } else {
       if (SystemUtil::isWindows() && $os === SystemUtil::WINDOWS) {
         $bin = $this->echoBat;
       } elseif (!SystemUtil::isWindows() && $os === SystemUtil::UNIX) {
         $bin = $this->echoSh;
       } else {
         return $this->markTestSkipped('Wrong platform for this test');
       }

       $builder = $this->build()->setPrefix($bin);
    }

    $builder->setEnv('DEFINED_VAR', 'this is an defined env value');

    foreach ($arguments as $arg) {
      $builder->add($arg);
    }

    $process = $builder->getProcess();
    $process->setEnhanceWindowsCompatibility(TRUE);

    $this->assertEchoProcess($process, $expectedArguments);
  }

  protected function assertEchoProcess($process, $expectedArguments) {
    $this->assertEquals(0, $process->run(), "Error: Process did not run correctly. It was run:\n".$process->getCommandLine()."\nExitCode 0 was expected\nOutput is:\n".$process->getOutput()."\n".$process->getErrorOutput());
    $this->assertEquals($expectedArguments, JSONConverter::create()->parse($process->getOutput()), "Command Called was:\n".$process->getCommandLine());
  }
  
  public static function provideEchoArguments() {
    $tests = array();

    $tests[] = Array(
      array('myargument'), array('myargument')
    );
    
    $tests[] = Array(
      array('my"argument'), array('my"argument')
    );
    
    $tests[] = Array(
      array("my'argument"), array("my'argument")
    );

    // the application itself has to make sure that the actual arguments are escaped correctly when in --arg="value" format. If value has an \ at the end it needs to be escaped
    $tests[] = Array(
      array('--paths="something\with\trailing\\"'), array('--paths="something\with\trailing\\\\"')
    );

    $tests[] = Array(
      array('%h%d%Y'), array('%h%d%Y')
    );

    $tests[] = Array(
      array('--format=%h%d%Y'), array('--format=%h%d%Y')
    );

    $tests[] = Array(
      array('--format="%h%d%Y"'), array('--format="%h%d%Y"')
    );

    $expandedTests = array();
    foreach (array(SystemUtil::WINDOWS, SystemUtil::UNIX) as $os) {
      foreach(array(TRUE, FALSE) as $withPHP) {
        foreach ($tests as $testArgs) {
          $expandedTests[] = array_merge($testArgs, array($os, $withPHP));
        }
      }
    }

    return $expandedTests;

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
