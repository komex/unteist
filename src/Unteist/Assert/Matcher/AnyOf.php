<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;

use Unteist\Exception\AssertFailException;
use Unteist\Assert\Assert;

/**
 * Class AnyOf
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class AnyOf extends AbstractMatcher
{
    /**
     * @param AbstractMatcher[] $expected
     */
    public function __construct(array $expected)
    {
        parent::__construct($expected);
    }

    /**
     * Get name of matcher.
     *
     * @return string
     */
    public function getName()
    {
        return 'AnyOf';
    }

    /**
     * Matcher condition.
     *
     * @param mixed $actual
     *
     * @throws \InvalidArgumentException
     * @return bool
     */
    protected function condition($actual)
    {
        /** @var AbstractMatcher $expected */
        foreach ($this->expected as $expected) {
            if (!($expected instanceof AbstractMatcher)) {
                throw new \InvalidArgumentException('Expects only AbstractMatcher objects.');
            }
            if ($expected->condition($actual) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $actual
     * @param string $message
     *
     * @throws AssertFailException
     */
    protected function fail($actual, $message)
    {
        $formatted = (empty($message) ? '' : $message . PHP_EOL);
        $formatted .= sprintf(
            'It was expected the successful completion of at least one condition of %d.',
            count($this->expected)
        );
        parent::fail($actual, $formatted);
    }
}
