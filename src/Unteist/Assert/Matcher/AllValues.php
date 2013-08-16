<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;

/**
 * Class AllValues
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class AllValues extends AbstractMatcher
{
    /**
     * @var int
     */
    protected $number;
    /**
     * @var AbstractMatcher
     */
    protected $expected;

    /**
     * @param AbstractMatcher $expected
     */
    public function __construct(AbstractMatcher $expected)
    {
        $this->expected = $expected;
    }

    /**
     * Matcher condition.
     *
     * @param array $actual
     *
     * @throws \InvalidArgumentException If $actual variable not an array or instance of Traversable.
     * @return bool
     */
    protected function condition($actual)
    {
        if (!(is_array($actual) || ($actual instanceof \Traversable))) {
            throw new \InvalidArgumentException('Actual variable must be an array or instance of Traversable.');
        }
        foreach ($actual as $number => $value) {
            $this->number = $number;
            if (!$this->expected->condition($value)) {
                return false;
            }
        }

        return true;
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
        return $this->expected->getFailDescription($actual[$this->number]) . sprintf(
            ' on element #%d of %d',
            ($this->number + 1),
            count($actual)
        );
    }
}
