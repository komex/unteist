<?php
/**
 * This file is a part of unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Assert\Matcher;

use Unteist\Assert\Matcher\SameInstance;

/**
 * Class SameInstanceTest
 *
 * @package Tests\Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class SameInstanceTest extends \PHPUnit_Framework_TestCase
{
    public function testGoodWay()
    {
        $class = new SameInstance('PHPUnit_Framework_TestCase');
        $class->match($this);
    }

    /**
     * @expectedException \Unteist\Exception\TestFailException
     * @expectedExceptionMessage <Tests\Unteist\Assert\Matcher\SameInstanceTest object> is instance of "SomeClass"
     */
    public function testBadWay()
    {
        $class = new SameInstance('SomeClass');
        $class->match($this);
    }
}
