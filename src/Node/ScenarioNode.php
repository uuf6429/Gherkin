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
 * Represents Gherkin Scenario.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class ScenarioNode implements ScenarioInterface, NamedScenarioInterface
{
    use TaggedNodeTrait;

    /**
     * Initializes scenario.
     *
     * @param list<string> $tags
     * @param list<StepNode> $steps
     */
    public function __construct(
        private readonly ?string $title,
        private readonly array $tags,
        private readonly array $steps,
        private readonly string $keyword,
        private readonly int $line,
    ) {
    }

    public function getNodeType()
    {
        return 'Scenario';
    }

    /**
     * Returns scenario title.
     *
     * @return string|null
     *
     * @deprecated you should use {@see self::getName()} instead as this method will be removed in the next
     *             major version
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function getName(): ?string
    {
        return $this->title;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function hasSteps()
    {
        return $this->steps !== [];
    }

    public function getSteps()
    {
        return $this->steps;
    }

    public function getKeyword()
    {
        return $this->keyword;
    }

    public function getLine()
    {
        return $this->line;
    }
}
