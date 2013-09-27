<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Tests\Unteist\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Processor;
use Unteist\Configuration\ConfigurationValidator;

/**
 * Class ConfigurationValidatorTest
 *
 * @package Tests\Unteist\Configuration
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class ConfigurationValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Processor
     */
    protected static $processor;
    /**
     * @var ConfigurationValidator
     */
    protected static $validator;

    /**
     * Precondition.
     */
    public static function setUpBeforeClass()
    {
        self::$processor = new Processor();
        self::$validator = new ConfigurationValidator();
    }

    public function testContextSection()
    {
        $method = new \ReflectionMethod(self::$validator, 'getContextSection');
        $method->setAccessible(true);
        /** @var ArrayNodeDefinition $section */
        $section = $method->invoke(self::$validator, false);
        $node = $section->getNode(true);
        $this->assertEquals('context', $node->getName());
        $this->assertFalse($node->hasDefaultValue());
        $this->assertFalse($node->isRequired());
        var_dump($node->finalize([]));
    }
}
