<?php
/**
 * This file is a part of Unteist project.
 *
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Filter;

/**
 * Class MethodsFilter
 *
 * @package Unteist\Filter
 */
class MethodsFilter implements MethodsFilterInterface
{
    /**
     * Filter TestCase methods.
     *
     * @param \ReflectionMethod[] $methods Methods to filter
     *
     * @return \ReflectionMethod[]
     */
    public function filter(array $methods)
    {
        $tests = [];
        foreach ($methods as $test) {
            if ($test instanceof \ReflectionMethod && $test->isPublic() && !$test->isAbstract()) {
                if (strlen($test->name) > 4 && substr($test->name, 0, 4) === 'test') {
                    array_push($tests, $test);
                }
            }
        }

        return $tests;
    }

}