<?php
/**
 * This file is a part of unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Assert\Matcher;

use Unteist\Assert\Matcher\EqualTo;

/**
 * Class EqualToTest
 *
 * @package Tests\Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class EqualToTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function dpGoodWay()
    {
        return [
            [5, 5],
            [true, true],
            [false, false],
            ['str', 'str'],
            [['a' => 'b', 'c' => 'd'], ['c' => 'd', 'a' => 'b']],
            [0, 'string'],
            [4.2, '4.2 str'],
            [null, ''],
            [new \ArrayObject(), new \ArrayObject()],
        ];
    }

    /**
     * @return array
     */
    public function dpBadWay()
    {
        return [
            [-7, -3],
            [0, 0.2],
            ['str1', 'str2'],
            [['a' => 'b', 'c' => 'd'], ['d' => 'c', 'b' => 'a']],
            [null, 'f'],
        ];
    }

    /**
     * @param mixed $actual
     * @param mixed $expected
     *
     * @dataProvider dpGoodWay
     */
    public function testGoodWay($actual, $expected)
    {
        $class = new EqualTo($expected);
        $class->match($actual);
    }

    /**
     * @param mixed $actual
     * @param mixed $expected
     *
     * @dataProvider dpBadWay
     * @expectedException \Unteist\Exception\TestFailException
     * @expectedExceptionMessage Failed asserting that variables are equals:
     */
    public function testBadWay($actual, $expected)
    {
        $class = new EqualTo($expected);
        $class->match($actual);
    }
}
