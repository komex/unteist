<?php
/**
 * This file is a part of unteist project.
 *
 * (c) Andrey Kolchenko <komexx@gmail.com>
 */

namespace Tests\Unteist\Assert\Matcher;

use Unteist\Assert\Matcher\TypeOf;

/**
 * Class TypeOfTest
 *
 * @package Tests\Unteist\Assert\Matcher
 * @author Andrey Kolchenko <komexx@gmail.com>
 */
class TypeOfTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function dpConditionSuccess()
    {
        return [
            ['string', 'some string'],
            ['integer', 5],
            ['boolean', false],
            ['double', 0.2],
            ['array', []],
        ];
    }

    /**
     * @param string $type
     * @param mixed $target
     *
     * @dataProvider dpConditionSuccess
     */
    public function testConditionSuccess($type, $target)
    {
        $class = new TypeOf($type);
        $class->match($target);
    }

    /**
     * @expectedException \Unteist\Exception\TestFailException
     * @expectedExceptionMessage Failed asserting that <NULL variable> is type of string
     */
    public function testConditionFail()
    {
        $class = new TypeOf('string');
        $class->match(null);
    }
}
