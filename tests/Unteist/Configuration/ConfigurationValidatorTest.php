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

    /**
     * Test default context configuration.
     */
    public function testContextSection()
    {
        $node = $this->getNode('getContextSection', 'context');
        /** @var array $defaults */
        $defaults = $node->getDefaultValue();
        $this->assertCount(5, $defaults);
        $this->assertArrayHasKey('error', $defaults);
        $this->assertArrayHasKey('failure', $defaults);
        $this->assertArrayHasKey('incomplete', $defaults);
        $this->assertArrayHasKey('associations', $defaults);
        $this->assertArrayHasKey('levels', $defaults);
        $this->assertEquals('strategy.fail', $defaults['error']);
        $this->assertEquals('strategy.fail', $defaults['failure']);
        $this->assertEquals('strategy.incomplete', $defaults['incomplete']);
        $this->assertSame(['E_ALL'], $defaults['levels']);
        $this->assertInternalType('array', $defaults['associations']);
        $this->assertEmpty($defaults['associations']);
    }

    /**
     * Test default filter configuration.
     */
    public function testFilterSection()
    {
        $node = $this->getNode('getFiltersSection', 'filters');
        /** @var array $defaults */
        $defaults = $node->getDefaultValue();
        $this->assertCount(2, $defaults);
        $this->assertArrayHasKey('class', $defaults);
        $this->assertArrayHasKey('methods', $defaults);
        $this->assertSame(['filter.class.base'], $defaults['class']);
        $this->assertInternalType('array', $defaults['methods']);
        $this->assertEmpty($defaults['methods']);
    }

    /**
     * Test default logger configuration.
     */
    public function testLoggerSection()
    {
        $node = $this->getNode('getLoggerSection', 'logger');
        /** @var array $defaults */
        $defaults = $node->getDefaultValue();
        $this->assertCount(2, $defaults);
        $this->assertArrayHasKey('enabled', $defaults);
        $this->assertArrayHasKey('handlers', $defaults);
        $this->assertFalse($defaults['enabled']);
        $this->assertSame(['logger.handler.stream'], $defaults['handlers']);
    }

    /**
     * Get node definition from method.
     *
     * @param string $method Method name
     * @param string $name Root section name
     *
     * @return \Symfony\Component\Config\Definition\NodeInterface
     */
    private function getNode($method, $name)
    {
        $method = new \ReflectionMethod(self::$validator, $method);
        $method->setAccessible(true);
        /** @var ArrayNodeDefinition $section */
        $section = $method->invoke(self::$validator);
        $node = $section->getNode(true);
        $this->assertEquals($name, $node->getName());
        $this->assertTrue($node->hasDefaultValue());
        $this->assertFalse($node->isRequired());

        return $node;
    }
}
