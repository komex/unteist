<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Assert\Matcher;

use Unteist\Assert\Matcher\AnyValue;
use Unteist\Assert\Matcher\IdenticalTo;

/**
 * Class AnyValueTest
 *
 * @package Tests\Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class AnyValueTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Actual variable must be an array or instance of Traversable
     */
    public function testInvalidArgument()
    {
        $class = new AnyValue(new IdenticalTo(5));
        $class->match('not array');
    }

    public function testGoodWay()
    {
        $class = new AnyValue(new IdenticalTo(5));
        $class->match([1, 3, 5, 6]);
        $class->match(new \ArrayObject([5, 0, -1]));
        $class->match([5, 5]);
    }

    /**
     * @return array
     */
    public function dpBadWay()
    {
        return [
            [[1, 's', true]],
            [new \ArrayObject()],
            [[]],
        ];
    }

    /**
     * @param array $actual
     *
     * @dataProvider dpBadWay
     * @expectedException \Unteist\Exception\TestFailException
     * @expectedExceptionMessage It was expected the successful completion of condition at least one of
     */
    public function testBadWay($actual)
    {
        $class = new AnyValue(new IdenticalTo(5));
        $class->match($actual);
    }
}
