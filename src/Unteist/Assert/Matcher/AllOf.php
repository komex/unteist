<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;

use Unteist\Exception\TestFailException;
use Unteist\Assert\Assert;

/**
 * Class AllOf
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class AllOf extends AbstractMatcher
{
    /**
     * @var int
     */
    protected $number;

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
        return 'AllOf';
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
        foreach ($this->expected as $i => $expected) {
            if (!($expected instanceof AbstractMatcher)) {
                throw new \InvalidArgumentException('Expects only AbstractMatcher objects.');
            }
            if ($expected->condition($actual) === false) {
                $this->number = $i;

                return false;
            }
        }

        return true;
    }

    /**
     * @param mixed $actual
     * @param string $message
     *
     * @throws TestFailException
     */
    protected function fail($actual, $message)
    {
        $formatted = (empty($message) ? '' : $message . PHP_EOL);
        $formatted .= sprintf(
            'Expected successful completion of all conditions (%d), but the condition of matcher #%d is not satisfied.',
            count($this->expected),
            $this->number + 1
        );
        /** @var AbstractMatcher $matcher */
        $matcher = $this->expected[$this->number];
        $matcher->fail($actual, $formatted);
    }
}
