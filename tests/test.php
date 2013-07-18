<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <komexx@gmail.com>
 */

require 'vendor/autoload.php';

$finder = new \Symfony\Component\Finder\Finder();
$files = $finder->files()->in(__DIR__)->name('*Test.php');
$dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
$m = new \Unteist\Processor\MultiProc($dispatcher);
$m->addClassFilter(new \Unteist\Filter\ClassFilter());
$m->addMethodsFilter(new \Unteist\Filter\MethodsFilter());
$m->setSuites($files);
$m->run();