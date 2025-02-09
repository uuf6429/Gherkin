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
 * Represents Gherkin Background.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class BackgroundNode implements ScenarioLikeInterface
{
    /**
     * @param list<StepNode> $steps
     */
    public function __construct(
        private readonly ?string $title,
        private readonly array $steps,
        private readonly string $keyword,
        private readonly int $line,
    ) {
    }

    public function getNodeType()
    {
        return 'Background';
    }

    public function getTitle()
    {
        return $this->title;
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
