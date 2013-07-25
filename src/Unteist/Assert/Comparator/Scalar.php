<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Comparator;

use SebastianBergmann\Exporter\Exporter;
use Unteist\Exception\ComparisonFailure;


/**
 * Class Scalar
 *
 * @package Unteist\Assert\Comparator
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class Scalar implements ComparatorInterface
{
    /**
     * Returns whether the comparator can compare two values.
     *
     * @param mixed $expected The first value to compare
     * @param mixed $actual The second value to compare
     *
     * @return bool
     */
    public function accepts($expected, $actual)
    {
        return ((is_scalar($expected) XOR null === $expected) &&
            (is_scalar($actual) XOR null === $actual))
        // allow comparison between strings and objects featuring __toString()
        || (is_string($expected) && is_object($actual) && method_exists($actual, '__toString'))
        || (is_object($expected) && method_exists($expected, '__toString') && is_string($actual));
    }

    /**
     * Asserts that two values are equal.
     *
     * @param mixed $expected
     * @param mixed $actual
     * @param int $delta
     * @param bool $canonicalize
     * @param bool $ignore_case
     *
     * @throws \Unteist\Exception\ComparisonFailure If expected and actual elements are different.
     */
    public function assertEquals($expected, $actual, $delta = 0, $canonicalize = false, $ignore_case = false)
    {
        $expected_to_compare = $expected;
        $actual_to_compare = $actual;

        // Always compare as strings to avoid strange behaviour otherwise 0 == 'Foobar'
        if (is_string($expected) || is_string($actual)) {
            $expected_to_compare = (string)$expected_to_compare;
            $actual_to_compare = (string)$actual_to_compare;

            if ($ignore_case) {
                $expected_to_compare = strtolower($expected_to_compare);
                $actual_to_compare = strtolower($actual_to_compare);
            }
        }

        if ($expected_to_compare != $actual_to_compare) {
            $exporter = new Exporter();
            if (is_string($expected) && is_string($actual)) {
                throw new ComparisonFailure(
                    $exporter->export($expected),
                    $exporter->export($actual),
                    'Failed asserting that two strings are equal.'
                );
            }

            throw new ComparisonFailure('', '', sprintf(
                'Failed asserting that %s matches expected %s.',
                $exporter->export($actual),
                $exporter->export($expected)
            ));
        }
    }

} 