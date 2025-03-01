<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Loader;

use Behat\Gherkin\Exception\NodeException;
use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Node\StepNode;

/**
 * Loads a feature from cucumber's protobuf JSON format.
 *
 * Note: some PHPStan types below are less strict than the spec, to account for defensive code (e.g. `Feature.name` is
 * supposed to be mandatory, but we defined it as optional to account for the defensive `isset()`).
 *
 * @see https://github.com/cucumber/messages/blob/e1537b07e511feb6405ed9aa00261ff79d8a9710/jsonschema/GherkinDocument.json
 *
 * @phpstan-type TGherkinDocument array{feature?: TFeature, comments: list<TComment>}
 * @phpstan-type TLocation array{line: int, column?: int}
 * @phpstan-type TBackground array{
 *     location: TLocation,
 *     keyword: string,
 *     name: string,
 *     description: string,
 *     steps?: list<TStep>,
 *     id: string,
 * }
 * @phpstan-type TComment array{location: TLocation, text: string}
 * @phpstan-type TDataTable array{location: TLocation, rows: list<TTableRow>}
 * @phpstan-type TDocString array{location: TLocation, mediaType?: string, content: string, delimiter: string}
 * @phpstan-type TExamples array{
 *     location: TLocation,
 *     tags: list<TTag>,
 *     keyword: string,
 *     name: string,
 *     description: string,
 *     tableHeader?: TTableRow,
 *     tableBody: list<TTableRow>,
 *     id: string,
 * }
 * @phpstan-type TFeature array{
 *     location: TLocation,
 *     tags: list<TTag>,
 *     language: string,
 *     keyword: string,
 *     name?: string,
 *     description: string,
 *     children?: list<TFeatureChild>,
 * }
 * @phpstan-type TFeatureChild array{rule?: TRule, background?: TBackground, scenario?: TScenario}
 * @phpstan-type TRule array{
 *     location: TLocation,
 *     tags: list<TTag>,
 *     keyword: string,
 *     name: string,
 *     description: string,
 *     children?: list<TRuleChild>,
 *     id: string,
 * }
 * @phpstan-type TRuleChild array{background?: TBackground, scenario?: TScenario}
 * @phpstan-type TScenario array{
 *     location: TLocation,
 *     tags: list<TTag>,
 *     keyword: string,
 *     name?: string,
 *     description: string,
 *     steps?: array<array-key, TStep>,
 *     examples: array<array-key, TExamples>,
 *     id: string,
 * }
 * @phpstan-type TStep array{
 *     location: TLocation,
 *     keyword: string,
 *     keywordType?: 'Unknown'|'Context'|'Action'|'Outcome'|'Conjunction',
 *     text: string,
 *     docString?: TDocString,
 *     dataTable?: TDataTable,
 *     id: string,
 * }
 * @phpstan-type TTableCell array{location: TLocation, value: string}
 * @phpstan-type TTableRow array{location: TLocation, cells: list<TTableCell>, id: string}
 * @phpstan-type TTag array{location: TLocation, name: string, id: string}
 */
class CucumberNDJsonAstLoader implements LoaderInterface
{
    public function supports($resource)
    {
        return is_string($resource);
    }

    public function load($resource)
    {
        \assert(is_scalar($resource) || $resource instanceof \Stringable);

        return array_values(array_filter(array_map(
            static function ($line) use ($resource) {
                // @phpstan-ignore-next-line
                return self::getFeature(json_decode($line, true, 512, JSON_THROW_ON_ERROR), $resource);
            },
            file((string) $resource) ?: []
        )));
    }

    /**
     * @phpstan-param array{gherkinDocument?: TGherkinDocument} $json
     */
    private static function getFeature(array $json, string $filePath): ?FeatureNode
    {
        if (!isset($json['gherkinDocument']['feature'])) {
            return null;
        }

        $featureJson = $json['gherkinDocument']['feature'];

        return new FeatureNode(
            $featureJson['name'] ?? null,
            $featureJson['description'] ? trim($featureJson['description']) : null,
            self::getTags($featureJson),
            self::getBackground($featureJson),
            self::getScenarios($featureJson),
            $featureJson['keyword'],
            $featureJson['language'],
            preg_replace('/(?<=\\.feature).*$/', '', $filePath),
            $featureJson['location']['line']
        );
    }

    /**
     * @phpstan-param array{tags?: array<array-key, TTag>} $json
     *
     * @return list<string>
     */
    private static function getTags(array $json): array
    {
        return array_map(
            static fn (array $tag): string => (string) preg_replace('/^@/', '', $tag['name']),
            array_values($json['tags'] ?? [])
        );
    }

    /**
     * @phpstan-param TFeature $json
     *
     * @return list<ScenarioInterface>
     */
    private static function getScenarios(array $json): array
    {
        return array_values(
            array_map(
                static function (array $child): ScenarioInterface {
                    if ($child['scenario']['examples']) {
                        return new OutlineNode(
                            $child['scenario']['name'] ?? null,
                            self::getTags($child['scenario']),
                            self::getSteps($child['scenario']['steps'] ?? []),
                            self::getTables($child['scenario']['examples']),
                            $child['scenario']['keyword'],
                            $child['scenario']['location']['line']
                        );
                    }

                    return new ScenarioNode(
                        $child['scenario']['name'] ?? null,
                        self::getTags($child['scenario']),
                        self::getSteps($child['scenario']['steps'] ?? []),
                        $child['scenario']['keyword'],
                        $child['scenario']['location']['line']
                    );
                },
                array_filter(
                    $json['children'] ?? [],
                    static function ($child) {
                        return isset($child['scenario']);
                    }
                )
            )
        );
    }

    /**
     * @phpstan-param array{children?: list<array{background?: TBackground}>} $json
     */
    private static function getBackground(array $json): ?BackgroundNode
    {
        $backgrounds = array_filter(
            $json['children'] ?? [],
            static fn ($child) => isset($child['background']),
        );

        if (count($backgrounds) !== 1) {
            return null;
        }

        $background = array_shift($backgrounds);

        return new BackgroundNode(
            $background['background']['name'],
            self::getSteps($background['background']['steps'] ?? []),
            $background['background']['keyword'],
            $background['background']['location']['line']
        );
    }

    /**
     * @phpstan-param array<array-key, TStep> $items
     *
     * @return list<StepNode>
     */
    private static function getSteps(array $items): array
    {
        return array_map(
            static fn (array $item) => new StepNode(
                trim($item['keyword']),
                $item['text'],
                [],
                $item['location']['line'],
                trim($item['keyword'])
            ),
            array_values($items)
        );
    }

    /**
     * @phpstan-param array<array-key, TExamples> $items
     *
     * @return list<ExampleTableNode>
     */
    private static function getTables(array $items): array
    {
        return array_map(
            static function (array $tableJson): ExampleTableNode {
                $table = [];

                if (!isset($tableJson['tableHeader'])) {
                    throw new NodeException(
                        sprintf(
                            'Table header is required, but none was specified for the example on line %s.',
                            $tableJson['location']['line'],
                        )
                    );
                }

                $table[$tableJson['tableHeader']['location']['line']] = array_column($tableJson['tableHeader']['cells'], 'value');

                foreach ($tableJson['tableBody'] as $bodyRow) {
                    $table[$bodyRow['location']['line']] = array_column($bodyRow['cells'], 'value');
                }

                return new ExampleTableNode(
                    $table,
                    $tableJson['keyword'],
                    self::getTags($tableJson)
                );
            },
            array_values($items)
        );
    }
}
