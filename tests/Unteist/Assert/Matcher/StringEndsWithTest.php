<?php
/**
 * This file is a part of unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Assert\Matcher;

use Unteist\Assert\Matcher\StringEndsWith;

/**
 * Class StringEndsWithTest
 *
 * @package Tests\Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class StringEndsWithTest extends \PHPUnit_Framework_TestCase
{
    public function testGoodWay()
    {
        $class = new StringEndsWith('som');
        $class->match('some string with som');
    }

    /**
     * @expectedException \Unteist\Exception\TestFailException
     * @expectedExceptionMessage Failed asserting that "some string" ends with "som"
     */
    public function testBadWay()
    {
        $class = new StringEndsWith('som');
        $class->match('some string');
    }
}
