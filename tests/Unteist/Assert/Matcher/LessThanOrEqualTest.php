<?php
/**
 * This file is a part of unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Assert\Matcher;

use Unteist\Assert\Matcher\LessThanOrEqual;

/**
 * Class LessThanOrEqualTest
 *
 * @package Tests\Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class LessThanOrEqualTest extends \PHPUnit_Framework_TestCase
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
     * @param int|float $actual
     * @param int|float $expected
     *
     * @dataProvider dpGoodWay
     */
    public function testGoodWay($actual, $expected)
    {
        $class = new LessThanOrEqual($expected);
        $class->match($actual);
    }

    /**
     * @param int|float $expected
     * @param int|float $actual
     *
     * @dataProvider dpGoodWay
     * @expectedException \Unteist\Exception\TestFailException
     */
    public function testBadWay($expected, $actual)
    {
        $class = new LessThanOrEqual($expected);
        $class->match($actual);
    }

    /**
     * @expectedException \Unteist\Exception\TestFailException
     * @expectedExceptionMessage Failed asserting that 1 is less than or equal 0
     */
    public function testBadWayException()
    {
        $class = new LessThanOrEqual(0);
        $class->match(1);
    }
}
