<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Assert\Matcher;

use Unteist\Assert\Matcher\AnyMatcher;
use Unteist\Assert\Matcher\IsEmpty;
use Unteist\Assert\Matcher\StringEndsWith;
use Unteist\Assert\Matcher\TypeOf;
use Unteist\Assert\Matcher\Not;

/**
 * Class AnyMatcherTest
 *
 * @package Tests\Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class AnyMatcherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function dpGoodWay()
    {
        return [
            [[new TypeOf('integer'), new TypeOf('array'), new Not(new IsEmpty())]],
            [[new TypeOf('string')]],
            [[new Not(new TypeOf('array')), new StringEndsWith('ring')]],
        ];
    }

    /**
     * @param \Unteist\Assert\Matcher\AbstractMatcher[] $matchers
     *
     * @dataProvider dpGoodWay
     */
    public function testGoodWay(array $matchers)
    {
        $class = new AnyMatcher($matchers);
        $class->match('string');
    }

    /**
     * @return array
     */
    public function dpBadWay()
    {
        return [
            [[new TypeOf('integer'), new TypeOf('array'), new IsEmpty()]],
            [[new Not(new TypeOf('string')), new StringEndsWith('rings')]],
        ];
    }

    /**
     * @param \Unteist\Assert\Matcher\AbstractMatcher[] $matchers
     *
     * @dataProvider dpBadWay
     * @expectedException \Unteist\Exception\TestFailException
     * @expectedExceptionMessage Failed asserting that at least one condition of
     */
    public function testBadWay(array $matchers)
    {
        $class = new AnyMatcher($matchers);
        $class->match('string');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The set of matchers can't be empty.
     */
    public function testEmptyMatchersException()
    {
        new AnyMatcher([]);
    }
}
