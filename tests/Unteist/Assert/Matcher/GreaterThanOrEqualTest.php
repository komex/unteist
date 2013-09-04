<?php
/**
 * This file is a part of unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Assert\Matcher;

use Unteist\Assert\Matcher\GreaterThanOrEqual;

/**
 * Class GreaterThanOrEqualTest
 *
 * @package Tests\Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class GreaterThanOrEqualTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function dpGoodWay()
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
     * @dataProvider dpGoodWay
     */
    public function testGoodWay($expected, $actual)
    {
        $class = new GreaterThanOrEqual($expected);
        $class->match($actual);
    }

    /**
     * @param int|float $actual
     * @param int|float $expected
     *
     * @dataProvider dpGoodWay
     * @expectedException \Unteist\Exception\TestFailException
     */
    public function testBadWay($actual, $expected)
    {
        $class = new GreaterThanOrEqual($expected);
        $class->match($actual);
    }

    /**
     * @expectedException \Unteist\Exception\TestFailException
     * @expectedExceptionMessage Failed asserting that 0 is greater than or equal 1
     */
    public function testBadWayException()
    {
        $class = new GreaterThanOrEqual(1);
        $class->match(0);
    }
}
