<?php
/**
 * This file is a part of unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Assert\Matcher;

use Unteist\Assert\Matcher\Count;

/**
 * Class CountTest
 *
 * @package Tests\Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class CountTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            [['a'], 1],
            [[], 0],
            [[1, 2, 3], 3],
            [new \ArrayObject([true, false, 'ok']), 3],
        ];
    }

    /**
     * @param array|\Countable|\Traversable $actual
     * @param int $expected
     *
     * @dataProvider dataProvider
     */
    public function testGoodWay($actual, $expected)
    {
        $class = new Count($expected);
        $class->match($actual);
    }

    /**
     * @param array|\Countable|\Traversable $actual
     * @param int $expected
     *
     * @dataProvider dataProvider
     * @expectedException \Unteist\Exception\TestFailException
     */
    public function testBadWay($actual, $expected)
    {
        $expected++;
        $class = new Count($expected);
        $class->match($actual);
    }

    /**
     * @expectedException \Unteist\Exception\TestFailException
     * @expectedExceptionMessage Failed asserting that actual size 0 matches expected size 1
     */
    public function testBadWayException()
    {
        $class = new Count(1);
        $class->match([]);
    }
}
