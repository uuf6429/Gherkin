<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Gherkin\Keywords;

/**
 * Keywords holder interface.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * @phpstan-type TLanguage string
 * @phpstan-type TKeywordsArray array{feature: string, background: string, scenario: string, scenario_outline: string, examples: string, given: string, when: string, then: string, and: string, but: string}
 * @phpstan-type TMultiLanguageKeywords array<TLanguage, TKeywordsArray>
 * @phpstan-type TKeywordsString string
 * @phpstan-type TStepKeywordsType 'Given'|'When'|'Then'|'And'|'But'
 * @phpstan-type TGeneralKeywordsType 'Feature'|'Background'|'Scenario'|'Outline'|'Examples'|'Step'
 * @phpstan-type TKeywordsType TGeneralKeywordsType|TStepKeywordsType
 */
interface KeywordsInterface
{
    /**
     * Sets keywords holder language.
     *
     * @param TLanguage $language Language name
     *
     * @return void
     */
    public function setLanguage($language);

    /**
     * Returns Feature keywords (separated by "|").
     *
     * @phpstan-return TKeywordsString
     */
    public function getFeatureKeywords();

    /**
     * Returns Background keywords (separated by "|").
     *
     * @phpstan-return TKeywordsString
     */
    public function getBackgroundKeywords();

    /**
     * Returns Scenario keywords (separated by "|").
     *
     * @phpstan-return TKeywordsString
     */
    public function getScenarioKeywords();

    /**
     * Returns Scenario Outline keywords (separated by "|").
     *
     * @phpstan-return TKeywordsString
     */
    public function getOutlineKeywords();

    /**
     * Returns Examples keywords (separated by "|").
     *
     * @phpstan-return TKeywordsString
     */
    public function getExamplesKeywords();

    /**
     * Returns Given keywords (separated by "|").
     *
     * @phpstan-return TKeywordsString
     */
    public function getGivenKeywords();

    /**
     * Returns When keywords (separated by "|").
     *
     * @phpstan-return TKeywordsString
     */
    public function getWhenKeywords();

    /**
     * Returns Then keywords (separated by "|").
     *
     * @phpstan-return TKeywordsString
     */
    public function getThenKeywords();

    /**
     * Returns And keywords (separated by "|").
     *
     * @phpstan-return TKeywordsString
     */
    public function getAndKeywords();

    /**
     * Returns But keywords (separated by "|").
     *
     * @phpstan-return TKeywordsString
     */
    public function getButKeywords();

    /**
     * Returns all step keywords (separated by "|").
     *
     * @phpstan-return TKeywordsString
     */
    public function getStepKeywords();
}
