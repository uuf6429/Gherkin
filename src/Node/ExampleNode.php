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
 * Represents Gherkin Outline Example.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class ExampleNode implements ScenarioInterface, NamedScenarioInterface
{
    use TaggedNodeTrait;

    /**
     * @var list<StepNode>|null
     */
    private $steps;

    /**
     * Initializes outline.
     *
     * @param string $text The entire row as a string, e.g. "| 1 | 2 | 3 |"
     * @param list<string> $tags
     * @param list<StepNode> $outlineSteps
     * @param array<string, string> $tokens
     * @param int $line line number within the feature file
     * @param string|null $outlineTitle original title of the scenario outline
     * @param int|null $index the 1-based index of the row/example within the scenario outline
     */
    public function __construct(
        private readonly string $text,
        private readonly array $tags,
        private readonly array $outlineSteps,
        private readonly array $tokens,
        private readonly int $line,
        private readonly ?string $outlineTitle = null,
        private readonly ?int $index = null,
    ) {
    }

    public function getNodeType()
    {
        return 'Example';
    }

    public function getKeyword()
    {
        return $this->getNodeType();
    }

    /**
     * Returns the example row as a single string.
     *
     * @return string
     *
     * @deprecated you should normally not depend on the original row text, but if you really do, please switch
     *             to {@see self::getExampleText()} as this method will be removed in the next major version
     */
    public function getTitle()
    {
        return $this->text;
    }

    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Checks if outline has steps.
     *
     * @return bool
     */
    public function hasSteps()
    {
        return $this->outlineSteps !== [];
    }

    public function getSteps()
    {
        return $this->steps ??= $this->createExampleSteps();
    }

    /**
     * Returns example tokens.
     *
     * @return string[]
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    public function getLine()
    {
        return $this->line;
    }

    /**
     * Returns outline title.
     *
     * @return string|null
     */
    public function getOutlineTitle()
    {
        return $this->outlineTitle;
    }

    public function getName(): ?string
    {
        return "{$this->replaceTextTokens($this->outlineTitle ?? '')} #{$this->index}";
    }

    /**
     * Returns the example row as a single string.
     *
     * You should normally not need this, since it is an implementation detail.
     * If you need the individual example values, use {@see self::getTokens()}.
     * To get the fully-normalised/expanded title, use {@see self::getName()}.
     */
    public function getExampleText(): string
    {
        return $this->text;
    }

    /**
     * Creates steps for this example from abstract outline steps.
     *
     * @return list<StepNode>
     */
    protected function createExampleSteps()
    {
        $steps = [];
        foreach ($this->outlineSteps as $outlineStep) {
            $keyword = $outlineStep->getKeyword();
            $keywordType = $outlineStep->getKeywordType();
            $text = $this->replaceTextTokens($outlineStep->getText());
            $args = $this->replaceArgumentsTokens($outlineStep->getArguments());
            $line = $outlineStep->getLine();

            $steps[] = new StepNode($keyword, $text, $args, $line, $keywordType);
        }

        return $steps;
    }

    /**
     * Replaces tokens in arguments with row values.
     *
     * @param array<array-key, ArgumentInterface> $arguments
     *
     * @return array<array-key, ArgumentInterface>
     */
    protected function replaceArgumentsTokens(array $arguments)
    {
        foreach ($arguments as $num => $argument) {
            if ($argument instanceof TableNode) {
                $arguments[$num] = $this->replaceTableArgumentTokens($argument);
            }
            if ($argument instanceof PyStringNode) {
                $arguments[$num] = $this->replacePyStringArgumentTokens($argument);
            }
        }

        return $arguments;
    }

    /**
     * Replaces tokens in table with row values.
     *
     * @return TableNode
     */
    protected function replaceTableArgumentTokens(TableNode $argument)
    {
        $table = $argument->getTable();
        foreach ($table as $line => $row) {
            foreach (array_keys($row) as $col) {
                $table[$line][$col] = $this->replaceTextTokens($table[$line][$col]);
            }
        }

        return new TableNode($table);
    }

    /**
     * Replaces tokens in PyString with row values.
     *
     * @return PyStringNode
     */
    protected function replacePyStringArgumentTokens(PyStringNode $argument)
    {
        $strings = $argument->getStrings();
        foreach ($strings as $line => $string) {
            $strings[$line] = $this->replaceTextTokens($strings[$line]);
        }

        return new PyStringNode($strings, $argument->getLine());
    }

    /**
     * Replaces tokens in text with row values.
     *
     * @param string $text
     *
     * @return string
     */
    protected function replaceTextTokens($text)
    {
        foreach ($this->tokens as $key => $val) {
            $text = str_replace('<' . $key . '>', $val, $text);
        }

        return $text;
    }
}
