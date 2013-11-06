<?php
/**
 * This file is a part of unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Assert\Matcher;

use Unteist\Assert\Matcher\StringStartsWith;

/**
 * Class StringStartsWithTest
 *
 * @package Tests\Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class StringStartsWithTest extends \PHPUnit_Framework_TestCase
{
    public function testGoodWay()
    {
        $class = new StringStartsWith('som');
        $class->match('some string');
    }

    /**
     * @expectedException \Unteist\Exception\TestFailException
     * @expectedExceptionMessage Failed asserting that "another string ends with som" starts with "som"
     */
    public function testBadWay()
    {
        $class = new StringStartsWith('som');
        $class->match('another string ends with som');
    }
}
