<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

require 'vendor/autoload.php';
$delusion = \Delusion\Delusion::injection();
$delusion->addToBlackList('PHPUnit');
