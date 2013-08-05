<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;

use Unteist\Exception\TestFailException;

/**
 * Class EveryItem
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 * @property AbstractMatcher $expected
 */
class EveryItem extends AbstractMatcher
{
    /**
     * @var int
     */
    protected $number;

    /**
     * @param AbstractMatcher $expected
     */
    public function __construct(AbstractMatcher $expected)
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
        return 'EveryItem';
    }

    /**
     * Matcher condition.
     *
     * @param mixed $actual
     *
     * @throws \InvalidArgumentException If $actual variable not an array or instance of Traversable.
     * @return bool
     */
    protected function condition($actual)
    {
        if (!(is_array($actual) || ($actual instanceof \Traversable))) {
            throw new \InvalidArgumentException('Actual variable must be an array or instance of Traversable.');
        }
        $this->number = 0;
        foreach ($actual as $value) {
            if (!$this->expected->condition($value)) {
                return false;
            }
            $this->number++;
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
            'Completiotion failed on element #%d',
            $this->number
        );
        /** @var AbstractMatcher $matcher */
        $matcher = $this->expected[$this->number];
        $matcher->fail($actual, $formatted);
    }
}
