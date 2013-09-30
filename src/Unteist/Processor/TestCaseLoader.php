<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Processor;

use Unteist\Exception\FilterException;
use Unteist\TestCase;

/**
 * Class TestCaseLoader
 *
 * @package Unteist\Processor
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class TestCaseLoader
{
    /**
     * Load class from specified file.
     *
     * @param \SplFileInfo $file
     *
     * @return TestCase
     *
     * @throws FilterException If cannot open file
     * @throws FilterException If TestCase class does not exists in this file
     */
    public static function load(\SplFileInfo $file)
    {
        if (!$file->isReadable()) {
            throw new FilterException(sprintf('Cannot open file "%s"', $file->getRealPath()));
        }
        $loaded_classes = get_declared_classes();
        include_once $file;
        $loaded_classes = array_reverse(
            array_values(
                array_diff(get_declared_classes(), $loaded_classes)
            )
        );
        $name = $file->getBasename('.php');
        /** @var TestCase $class */
        foreach ($loaded_classes as $class) {
            if (preg_match('{^([\w\\\]+\\\)?' . $name . '$}', $class)) {
                if (is_subclass_of($class, '\\Unteist\\TestCase')) {
                    return new $class;
                }
            }
        }
        throw new FilterException(sprintf('TestCase class does not found in file "%s"', $file->getRealPath()));
    }
}
