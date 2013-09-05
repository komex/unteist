<?php
/**
 * This file is a part of unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Assert\Matcher;

use Unteist\Assert\Matcher\GreaterThan;

/**
 * Class GreaterThanTest
 *
 * @package Tests\Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class GreaterThanTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            [4, 5],
            [-7, -3],
            [0, 0.2],
        ];
    }

    /**
     * @param int|float $expected
     * @param int|float $actual
     *
     * @dataProvider dataProvider
     */
    public function testGoodWay($expected, $actual)
    {
        $class = new GreaterThan($expected);
        $class->match($actual);
    }

    /**
     * @param int|float $actual
     * @param int|float $expected
     *
     * @dataProvider dataProvider
     * @expectedException \Unteist\Exception\TestFailException
     */
    public function testBadWay($actual, $expected)
    {
        $class = new GreaterThan($expected);
        $class->match($actual);
    }

    /**
     * @expectedException \Unteist\Exception\TestFailException
     * @expectedExceptionMessage Failed asserting that 0 is greater than 0
     */
    public function testBadWayException()
    {
        $class = new GreaterThan(0);
        $class->match(0);
    }
}
