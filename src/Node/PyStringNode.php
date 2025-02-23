<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Node;

/**
 * Represents Gherkin PyString argument.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class PyStringNode implements ArgumentInterface
{
    /**
     * Initializes PyString.
     *
     * @param array<int, string> $strings String in form of [$stringLine]
     * @param int $line Line number where string been started
     */
    public function __construct(
        private readonly array $strings,
        private readonly int $line,
    ) {
    }

    public function getNodeType()
    {
        return 'PyString';
    }

    /**
     * Returns entire PyString lines set.
     *
     * @return array<int, string>
     */
    public function getStrings()
    {
        return $this->strings;
    }

    /**
     * Returns raw string.
     *
     * @return string
     */
    public function getRaw()
    {
        return implode("\n", $this->strings);
    }

    /**
     * Converts PyString into string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getRaw();
    }

    public function getLine()
    {
        return $this->line;
    }
}
