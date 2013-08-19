<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;

/**
 * Class ArrayHasKey
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class ArrayHasKey extends AbstractMatcher
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @param string $key
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * Matcher condition.
     *
     * @param array $actual Original array
     *
     * @throws \InvalidArgumentException If variable is not an array
     * @return bool
     */
    protected function condition($actual)
    {
        if (!is_array($actual)) {
            throw new \InvalidArgumentException('Specified variable must be an array.');
        }

        return array_key_exists($this->key, $actual);
    }

    /**
     * Get description for error output.
     *
     * @param array $actual
     *
     * @return string
     */
    protected function getFailDescription($actual)
    {
        return 'array has key "' . $this->key . '"';
    }
}
