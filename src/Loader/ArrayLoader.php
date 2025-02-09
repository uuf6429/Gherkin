<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Loader;

use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Node\TableNode;

/**
 * From-array loader.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * @phpstan-type TArrayResource array{features: array<TLine, TFeatureHash>}|array{feature?: TFeatureHash}
 * @phpstan-type TLine int
 * @phpstan-type TFeatureHash array{
 *     title?: string|null,
 *     description?: string|null,
 *     tags?: list<string>,
 *     keyword?: string,
 *     language?: string,
 *     line?: int,
 *     scenarios?: array<TLine, TOutlineHash|TScenarioHash>,
 *     background?: TBackgroundHash|null,
 * }
 * @phpstan-type TScenarioHash array{
 *     type?: string,
 *     title?: string|null,
 *     tags?: list<string>,
 *     keyword?: string,
 *     line?: int,
 *     steps?: array<TLine, TStepHash>,
 * }
 * @phpstan-type TOutlineHash array{
 *     type: 'outline',
 *     title?: string|null,
 *     tags?: list<string>,
 *     keyword?: string,
 *     line?: int,
 *     steps?: array<TLine, TStepHash>,
 *     examples?: array<array-key, TExampleHash>,
 * }
 * @phpstan-type TBackgroundHash array{
 *     title?: string|null,
 *     keyword?: string,
 *     line?: int,
 *     steps?: array<TLine, TStepHash>,
 * }
 * @phpstan-type TStepHash array{
 *     keyword_type?: string,
 *     type?: string,
 *     text?: string|null,
 *     keyword?: string,
 *     line?: int,
 *     arguments?: array<array-key, TArgumentHash>,
 * }
 * @phpstan-type TExampleHash array{table: TTable, tags?: list<string>}
 * @phpstan-type TExampleHashOrTable TExampleHash|TTable
 * @phpstan-type TArgumentHash TTableArgumentHash|TPyStringArgumentHash
 * @phpstan-type TTableArgumentHash array{type: 'table', rows: TTable}
 * @phpstan-type TPyStringArgumentHash array{type: 'pystring', line?: int|null, text: string}
 *
 * @phpstan-import-type TTable from ExampleTableNode
 */
class ArrayLoader implements LoaderInterface
{
    /**
     * Checks if current loader supports provided resource.
     *
     * @param mixed $resource Resource to load
     *
     * @return bool
     */
    public function supports($resource)
    {
        return is_array($resource) && (isset($resource['features']) || isset($resource['feature']));
    }

    /**
     * Loads features from provided resource.
     *
     * @phpstan-param TArrayResource $resource Resource to load
     *
     * @return list<FeatureNode>
     */
    public function load($resource)
    {
        $features = [];

        if (isset($resource['features'])) {
            foreach ($resource['features'] as $index => $hash) {
                $feature = $this->loadFeatureHash($hash, $index);
                $features[] = $feature;
            }
        } elseif (isset($resource['feature'])) {
            $feature = $this->loadFeatureHash($resource['feature']);
            $features[] = $feature;
        }

        return $features;
    }

    /**
     * Loads feature from provided feature hash.
     *
     * @phpstan-param TFeatureHash $hash Feature hash
     * @phpstan-param TLine $line
     *
     * @return FeatureNode
     */
    protected function loadFeatureHash(array $hash, $line = 0)
    {
        $hash = array_merge(
            [
                'title' => null,
                'description' => null,
                'tags' => [],
                'keyword' => 'Feature',
                'language' => 'en',
                'line' => $line,
                'scenarios' => [],
            ],
            $hash
        );
        $background = isset($hash['background']) ? $this->loadBackgroundHash($hash['background']) : null;

        $scenarios = [];
        foreach ((array) $hash['scenarios'] as $scenarioIndex => $scenarioHash) {
            if (isset($scenarioHash['type']) && $scenarioHash['type'] === 'outline') {
                $scenarios[] = $this->loadOutlineHash($scenarioHash, $scenarioIndex);
            } else {
                $scenarios[] = $this->loadScenarioHash($scenarioHash, $scenarioIndex);
            }
        }

        return new FeatureNode($hash['title'], $hash['description'], $hash['tags'], $background, $scenarios, $hash['keyword'], $hash['language'], null, $hash['line']);
    }

    /**
     * Loads background from provided hash.
     *
     * @phpstan-param TBackgroundHash $hash Background hash
     *
     * @return BackgroundNode
     */
    protected function loadBackgroundHash(array $hash)
    {
        $hash = array_merge(
            [
                'title' => null,
                'keyword' => 'Background',
                'line' => 0,
                'steps' => [],
            ],
            $hash
        );

        $steps = $this->loadStepsHash($hash['steps']);

        return new BackgroundNode($hash['title'], $steps, $hash['keyword'], $hash['line']);
    }

    /**
     * Loads scenario from provided scenario hash.
     *
     * @phpstan-param TScenarioHash $hash Scenario hash
     * @phpstan-param TLine $line Scenario definition line
     *
     * @return ScenarioNode
     */
    protected function loadScenarioHash(array $hash, $line = 0)
    {
        $hash = array_merge(
            [
                'title' => null,
                'tags' => [],
                'keyword' => 'Scenario',
                'line' => $line,
                'steps' => [],
            ],
            $hash
        );

        $steps = $this->loadStepsHash($hash['steps']);

        return new ScenarioNode($hash['title'], $hash['tags'], $steps, $hash['keyword'], $hash['line']);
    }

    /**
     * Loads outline from provided outline hash.
     *
     * @phpstan-param TOutlineHash $hash Outline hash
     * @phpstan-param TLine $line Outline definition line
     *
     * @return OutlineNode
     */
    protected function loadOutlineHash(array $hash, $line = 0)
    {
        $hash = array_merge(
            [
                'title' => null,
                'tags' => [],
                'keyword' => 'Scenario Outline',
                'line' => $line,
                'steps' => [],
                'examples' => [],
            ],
            $hash
        );

        $steps = $this->loadStepsHash($hash['steps']);

        if (isset($hash['examples']['keyword'])) {
            $examplesKeyword = $hash['examples']['keyword'];
            unset($hash['examples']['keyword']);
        } else {
            $examplesKeyword = 'Examples';
        }

        assert(is_string($examplesKeyword));
        $examples = $this->loadExamplesHash($hash['examples'], $examplesKeyword);

        return new OutlineNode($hash['title'], $hash['tags'], $steps, $examples, $hash['keyword'], $hash['line']);
    }

    /**
     * Loads steps from provided hash.
     *
     * @phpstan-param array<TLine, TStepHash> $hash
     *
     * @return list<StepNode>
     */
    private function loadStepsHash(array $hash)
    {
        $steps = [];
        foreach ($hash as $stepIndex => $stepHash) {
            $steps[] = $this->loadStepHash($stepHash, $stepIndex);
        }

        return $steps;
    }

    /**
     * Loads step from provided hash.
     *
     * @phpstan-param TStepHash $hash Step hash
     * @phpstan-param TLine $line Step definition line
     *
     * @return StepNode
     */
    protected function loadStepHash(array $hash, $line = 0)
    {
        $hash = array_merge(
            [
                'keyword_type' => 'Given',
                'type' => 'Given',
                'text' => null,
                'keyword' => 'Scenario',
                'line' => $line,
                'arguments' => [],
            ],
            $hash
        );

        $arguments = [];
        foreach ($hash['arguments'] as $argumentHash) {
            if ($argumentHash['type'] === 'table') {
                $arguments[] = $this->loadTableHash($argumentHash['rows']);
            } elseif ($argumentHash['type'] === 'pystring') {
                $arguments[] = $this->loadPyStringHash($argumentHash, $hash['line'] + 1);
            }
        }

        return new StepNode($hash['type'], (string) $hash['text'], $arguments, $hash['line'], $hash['keyword_type']);
    }

    /**
     * Loads table from provided hash.
     *
     * @phpstan-param TTable $hash Table hash
     *
     * @return TableNode
     */
    protected function loadTableHash(array $hash)
    {
        return new TableNode($hash);
    }

    /**
     * Loads PyString from provided hash.
     *
     * @phpstan-param TPyStringArgumentHash $hash PyString hash
     * @phpstan-param TLine $line
     *
     * @return PyStringNode
     */
    protected function loadPyStringHash(array $hash, $line = 0)
    {
        $line = $hash['line'] ?? $line;

        $strings = [];
        foreach (explode("\n", $hash['text']) as $string) {
            $strings[] = $string;
        }

        return new PyStringNode($strings, $line);
    }

    /**
     * Processes cases when examples are in the form of array of arrays
     * OR in the form of array of objects.
     *
     * @phpstan-param TExampleHashOrTable|list<TExampleHashOrTable> $examplesHash
     *
     * @return list<ExampleTableNode>
     */
    private function loadExamplesHash(array $examplesHash, string $examplesKeyword): array
    {
        if ($this->isSingleTableExampleHash($examplesHash)) {
            return [$this->loadExampleHash($examplesHash, $examplesKeyword)];
        }

        $examples = [];

        foreach ($examplesHash as $exampleHash) {
            $examples[] = $this->loadExampleHash($exampleHash, $examplesKeyword);
        }

        return $examples;
    }

    /**
     * @phpstan-param TExampleHashOrTable $hash
     */
    private function loadExampleHash(array $hash, string $keyword): ExampleTableNode
    {
        if ($this->isObjectExampleHash($hash)) {
            // we have an example as an object; hence there could be tags
            return new ExampleTableNode($hash['table'], $keyword, $hash['tags'] ?? []);
        }

        // example as an array
        return new ExampleTableNode($hash, $keyword);
    }

    /**
     * @phpstan-param TExampleHashOrTable|list<TExampleHashOrTable> $hash
     *
     * @phpstan-assert-if-true TExampleHashOrTable $hash
     *
     * @phpstan-assert-if-false list<TExampleHashOrTable> $hash
     */
    private function isSingleTableExampleHash(array $hash): bool
    {
        return !isset($hash[0]);
    }

    /**
     * @phpstan-param TExampleHashOrTable $hash
     *
     * @phpstan-assert-if-true TExampleHash $hash
     *
     * @phpstan-assert-if-false TTable $hash
     */
    private function isObjectExampleHash(array $hash): bool
    {
        return isset($hash['table']);
    }
}
