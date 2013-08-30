<?php
/**
 * This file is a part of unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Assert\Matcher;

use Unteist\Assert\Matcher\LessThan;

/**
 * Class LessThanTest
 *
 * @package Tests\Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class LessThanTest extends \PHPUnit_Framework_TestCase
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
        $class = new LessThan($expected);
        $class->match($actual);
    }

    /**
     * @param int|float $actual
     * @param int|float $expected
     *
     * @dataProvider dpGoodWay
     * @expectedException \Unteist\Exception\TestFailException
     */
    public function testBadWay($expected, $actual)
    {
        $class = new LessThan($expected);
        $class->match($actual);
    }

    /**
     * @expectedException \Unteist\Exception\TestFailException
     * @expectedExceptionMessage Failed asserting that 0 is less than 0
     */
    public function testBadWayException()
    {
        $class = new LessThan(0);
        $class->match(0);
    }
}
