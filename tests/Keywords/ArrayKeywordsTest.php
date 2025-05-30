<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Keywords;

use Behat\Gherkin\Keywords\ArrayKeywords;
use Behat\Gherkin\Keywords\KeywordsInterface;
use Behat\Gherkin\Node\StepNode;

class ArrayKeywordsTest extends KeywordsTestCase
{
    protected static function getKeywords(): KeywordsInterface
    {
        return new ArrayKeywords(static::getKeywordsArray() + [
            // ArrayKeywords assumes internally that `en` is always a supported language
            'en' => [
                'and' => 'And|*',
                'background' => 'Background',
                'but' => 'But|*',
                'examples' => 'Scenarios|Examples',
                'feature' => 'Business Need|Ability|Feature',
                'given' => 'Given|*',
                'name' => 'English',
                'native' => 'English',
                'rule' => 'Rule',
                'scenario' => 'Scenario|Example',
                'scenario_outline' => 'Scenario Template|Scenario Outline',
                'then' => 'Then|*',
                'when' => 'When|*',
            ],
        ]);
    }

    protected static function getKeywordsArray(): array
    {
        return [
            'with_special_chars' => [
                'and' => 'And/foo',
                'background' => 'Background.',
                'but' => 'But[',
                'examples' => 'Examples|Scenarios',
                'feature' => 'Feature|Business Need|Ability',
                'given' => 'Given',
                'name' => 'English',
                'native' => 'English',
                'scenario' => 'Scenario',
                'scenario_outline' => 'Scenario Outline|Scenario Template',
                'then' => 'Then',
                'when' => 'When',
            ],
        ];
    }

    protected static function getSteps(string $keywords, string $text, int &$line, ?string $keywordType): array
    {
        $steps = [];
        foreach (explode('|', $keywords) as $keyword) {
            if (str_contains($keyword, '<')) {
                $keyword = mb_substr($keyword, 0, -1);
            }

            $steps[] = new StepNode($keyword, $text, [], $line++, $keywordType);
        }

        return $steps;
    }
}
