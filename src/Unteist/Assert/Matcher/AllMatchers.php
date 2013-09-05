<?php
/**
 * This file is a part of Unteist project.
 *
 * (c) Andrey Kolchenko <andrey@kolchenko.me>
 */

namespace Unteist\Assert\Matcher;

/**
 * Class AllMatchers
 *
 * @package Unteist\Assert\Matcher
 * @author Andrey Kolchenko <andrey@kolchenko.me>
 */
class AllMatchers extends AbstractMatcher
{
    /**
     * @var int
     */
    protected $number;
    /**
     * @var AbstractMatcher[]
     */
    protected $matchers;

    /**
     * @param AbstractMatcher[] $matchers
     *
     * @throws \InvalidArgumentException If the set of matchers is empty
     */
    public function __construct(array $matchers)
    {
        if (empty($matchers)) {
            throw new \InvalidArgumentException('The set of matchers can\'t be empty.');
        }
        $this->matchers = $matchers;
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
        foreach ($this->matchers as $i => $matcher) {
            if (!($matcher instanceof AbstractMatcher)) {
                throw new \InvalidArgumentException('Expects only AbstractMatcher objects.');
            }
            if ($matcher->condition($actual) === false) {
                $this->number = $i;

                return false;
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function fail($actual, $message)
    {
        $formatted = (empty($message) ? '' : $message . PHP_EOL);
        $formatted .= sprintf(
            'Expected successful completion of all conditions (%d), but the condition of matcher #%d is not satisfied:',
            count($this->matchers),
            $this->number + 1
        );
        /** @var AbstractMatcher $matcher */
        $matcher = $this->matchers[$this->number];
        $matcher->fail($actual, $formatted);
    }

    /**
     * Get description for error output.
     *
     * @param mixed $actual
     *
     * @return string
     */
    protected function getFailDescription($actual)
    {
        return '';
    }
}
