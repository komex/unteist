<?php
/**
 * This file is a part of unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Assert\Matcher;

use Unteist\Assert\Matcher\ArrayHasKey;

/**
 * Class ArrayHasKeyTest
 *
 * @package Tests\Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class ArrayHasKeyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $array = [
        'numeric key 1',
        'key' => 'value',
        'key2' => 'value',
        'numeric key 2',
        'key3' => 'value',
    ];

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            ['key'],
            ['key2'],
            ['key3'],
            [0],
            [1],
        ];
    }

    /**
     * @param string $key
     *
     * @dataProvider dataProvider
     */
    public function testGoodWay($key)
    {
        $class = new ArrayHasKey($key);
        $class->match($this->array);
    }

    /**
     * @expectedException \Unteist\Exception\TestFailException
     * @expectedExceptionMessage Failed asserting that array has key "not exits"
     */
    public function testBadWayException()
    {
        $class = new ArrayHasKey('not exits');
        $class->match($this->array);
    }
}
