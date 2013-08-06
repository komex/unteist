#!/usr/bin/env php
<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

require 'vendor/autoload.php';

$app = new \Unteist\Console\Unteist('Unteist launcher', '1.0.0');
$app->run();