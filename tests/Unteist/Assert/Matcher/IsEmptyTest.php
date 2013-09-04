<?php
/**
 * This file is a part of unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Assert\Matcher;

use Unteist\Assert\Matcher\IsEmpty;

/**
 * Class IsEmptyTest
 *
 * @package Tests\Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class IsEmptyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function dpGoodWay()
    {
        return [
            [''],
            [0],
            [false],
            [[]],
        ];
    }

    /**
     * @return array
     */
    public function dpBadWay()
    {
        return [
            ['str'],
            [0.2],
            [true],
            [['a', 'b', 'c', 'd']],
            [new \ArrayObject()],
        ];
    }

    /**
     * @param mixed $actual
     *
     * @dataProvider dpGoodWay
     */
    public function testGoodWay($actual)
    {
        $class = new IsEmpty();
        $class->match($actual);
    }

    /**
     * @param mixed $actual
     *
     * @dataProvider dpBadWay
     * @expectedException \Unteist\Exception\TestFailException
     * @expectedExceptionMessage is empty
     */
    public function testBadWay($actual)
    {
        $class = new IsEmpty();
        $class->match($actual);
    }
}
