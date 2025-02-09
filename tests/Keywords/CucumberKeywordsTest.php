<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Keywords;

use Behat\Gherkin\Keywords\CucumberKeywords;
use Behat\Gherkin\Keywords\KeywordsInterface;
use Behat\Gherkin\Node\StepNode;
use Symfony\Component\Yaml\Yaml;
use Tests\Behat\Gherkin\FileReaderTrait;

class CucumberKeywordsTest extends KeywordsTestCase
{
    use FileReaderTrait;

    protected static function getKeywords(): KeywordsInterface
    {
        return new CucumberKeywords(__DIR__ . '/../Fixtures/i18n.yml');
    }

    protected static function getKeywordsArray(): array
    {
        $data = self::readFile(__DIR__ . '/../Fixtures/i18n.yml');

        // @phpstan-ignore-next-line
        return Yaml::parse($data);
    }

    protected static function getSteps(string $keywords, string $text, int &$line, ?string $keywordType): array
    {
        $steps = [];
        foreach (explode('|', mb_substr($keywords, 2)) as $keyword) {
            if (str_contains($keyword, '<')) {
                $keyword = mb_substr($keyword, 0, -1);
            }

            $steps[] = new StepNode($keyword, $text, [], $line++, $keywordType);
        }

        return $steps;
    }
}
