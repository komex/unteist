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
     * Prepare case.
     */
    public static function setUpBeforeClass()
    {
        self::$logger = new Logger('test');
        self::$logger->pushHandler(new NullHandler());
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
        $meta = new TestMeta('class', 'method', $modifiers, self::$logger);
        $this->assertEquals($expected, $meta->getDataProvider());
    }

    /**
     * @return array
     */
    public function dpExpectedException()
    {
        return [
            [['expectedException' => 'Exception'], 'Exception'],
            [['expectedException' => true]],
            [['expectedException' => []]],
            [['expectedException' => '']],
            [['ExpectedException' => 'dp']],
            [[]],
        ];
    }

    /**
     * @param array $modifiers
     * @param string $expected
     *
     * @dataProvider dpExpectedException
     */
    public function testExpectedException(array $modifiers, $expected = null)
    {
        $meta = new TestMeta('class', 'method', $modifiers, self::$logger);
        $this->assertEquals($expected, $meta->getExpectedException());
    }
}
