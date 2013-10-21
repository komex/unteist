<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

require 'vendor/autoload.php';
$delusion = \Delusion\Delusion::injection();
$delusion->setStrategy($delusion::STRATEGY_DENY);
$delusion->addToWhiteList('Symfony\\Component\\Console\\Input\\');
