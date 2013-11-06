<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Meta;

use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Unteist\Meta\TestMeta;

/**
 * Class TestMetaTest
 *
 * @package Tests\Unteist\Meta
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class TestMetaTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Logger
     */
    protected static $logger;
    /**
     * @var TestMeta
     */
    protected $meta;

    /**
     * Prepare case.
     */
    public static function setUpBeforeClass()
    {
        self::$logger = new Logger('test');
        self::$logger->pushHandler(new NullHandler());
    }

    /**
     * Reset meta before each test.
     */
    public function setUp()
    {
        $this->meta = new TestMeta('class', 'method', [], self::$logger);
    }

    /**
     * @return array
     */
    public function dpDataProvider()
    {
        return [
            [['dataProvider' => 'dp'], 'dp'],
            [['dataProvider' => 'method']],
            [['dataProvider' => true]],
            [['dataProvider' => []]],
            [['dataProvider' => '']],
            [['Dataprovider' => 'dp']],
            [[]],
        ];
    }

    /**
     * @param array $modifiers
     * @param string $expected
     *
     * @dataProvider dpDataProvider
     */
    public function testDataProvider(array $modifiers, $expected = null)
    {
        $method = new \ReflectionMethod($this->meta, 'setDataProvider');
        $method->setAccessible(true);
        $method->invoke($this->meta, $modifiers);
        $this->assertEquals($expected, $this->meta->getDataProvider());
    }

    /**
     * @return array
     */
    public function dpExpectedException()
    {
        return [
            [['expectedException' => 'Exception'], 'Exception'],
            [['expectedException' => '']],
            [['ExpectedException' => 'dp']],
            [[]],
        ];
    }

    /**
     * @param array $annotations
     * @param string $expected
     *
     * @dataProvider dpExpectedException
     */
    public function testExpectedException(array $annotations, $expected = null)
    {
        $method = new \ReflectionMethod($this->meta, 'setExpectedException');
        $method->setAccessible(true);
        $method->invoke($this->meta, $annotations);
        $this->assertSame($expected, $this->meta->getExpectedException());
    }

    public function testExpectedExceptionMessage()
    {
        $method = new \ReflectionMethod($this->meta, 'setExpectedException');
        $method->setAccessible(true);

        $method->invoke($this->meta, ['expectedExceptionMessage' => 'Message']);
        $this->assertEmpty(
            $this->meta->getExpectedExceptionMessage(),
            'Message must be empty if exception does not set'
        );

        $method->invoke($this->meta, ['expectedException' => 'Exception', 'expectedExceptionMessage' => 'Message']);
        $this->assertSame('Message', $this->meta->getExpectedExceptionMessage());
    }

    public function testExpectedExceptionCode()
    {
        $method = new \ReflectionMethod($this->meta, 'setExpectedException');
        $method->setAccessible(true);

        $method->invoke($this->meta, ['expectedExceptionCode' => 5]);
        $this->assertNull($this->meta->getExpectedExceptionCode(), 'Code must be NULL if exception does not set');

        $method->invoke($this->meta, ['expectedException' => 'Exception', 'expectedExceptionCode' => 5]);
        $this->assertSame(5, $this->meta->getExpectedExceptionCode());

        $method->invoke($this->meta, ['expectedException' => 'Exception', 'expectedExceptionCode' => '76']);
        $this->assertSame(76, $this->meta->getExpectedExceptionCode());
    }

    public function testDependsDefault()
    {
        $method = new \ReflectionMethod($this->meta, 'setDataProvider');
        $method->setAccessible(true);
        $method->invoke($this->meta, []);
        $this->assertEmpty($this->meta->getDependencies());
        $this->assertInternalType('array', $this->meta->getDependencies());
    }

    /**
     * @return array
     */
    public function dpDepends()
    {
        return [
            ['!@*>', []],
            ['test1, test2, test1', ['test1', 'test2']],
            ['test1,method, test2#$, test1', ['test1', 'test2']],
            ['method, method', []],
        ];
    }

    /**
     * @param string $depends
     * @param array $expected
     *
     * @dataProvider dpDepends
     */
    public function testDepends($depends, array $expected)
    {
        $method = new \ReflectionMethod($this->meta, 'setDependencies');
        $method->setAccessible(true);
        $method->invoke($this->meta, ['depends' => $depends]);
        $this->assertEquals($expected, $this->meta->getDependencies());
    }
}
