<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

require __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'bootstrap.php';

$application = new Application();


$application
  ->register('echo')
  ->setDefinition(array(
      new InputOption('location', NULL, InputOption::VALUE_REQUIRED),
      new InputOption('flag'),
  ))
  ->setCode(function (InputInterface $input, OutputInterface $output) {
    $output->writeln(json_encode(array(
      $input->getOption('location'),
      $input->getOption('flag')
    )));
  })
;

exit($application->run());
