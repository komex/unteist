#!/usr/bin/env php
<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

require 'vendor/autoload.php';

$app = new \Symfony\Component\Console\Application('Unteist', '1.0.0');
$app->add(new \Unteist\Console\RunCommand());
$app->run();