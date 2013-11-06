<?php
/**
 * This file is a part of unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Assert\Matcher;

use Unteist\Assert\Matcher\StringContains;

/**
 * Class StringContainsTest
 *
 * @package Tests\Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class StringContainsTest extends \PHPUnit_Framework_TestCase
{
    public function testGoodWay()
    {
        $class = new StringContains('som');
        $class->match('string with som in center');
    }

    /**
     * @expectedException \Unteist\Exception\TestFailException
     * @expectedExceptionMessage Failed asserting that "just a string" contains "som"
     */
    public function testBadWay()
    {
        $class = new StringContains('som');
        $class->match('just a string');
    }
}
