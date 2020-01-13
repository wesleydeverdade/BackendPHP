<?php

require_once __DIR__ . '/vendor/autoload.php';

use Reweb\Job\Backend;

$BancoPlanetaCyber = new Backend\BancoPlanetaCyber;

//var_dump($BancoPlanetaCyber->deposito(666,1909,200));
//var_dump($BancoPlanetaCyber->deposito(123456,1903,400));
//var_dump($BancoPlanetaCyber->transferencia(123456,1903,666,10.5));
//var_dump($BancoPlanetaCyber->exibeSaldo(123456,1903));