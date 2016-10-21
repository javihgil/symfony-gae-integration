<?php
$loader = require __DIR__.'/../../autoload.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

header('Content-type: text/plain');
set_time_limit(0);
$application = new Application(new AppKernel('prod', false));
$application->setAutoExit(false);

$input = new ArrayInput(['command' => 'cache:clear', '--no-warmup' => null, '--env' => 'prod', ]);
$output = new BufferedOutput();
$application->run($input, $output);
echo $output->fetch();
