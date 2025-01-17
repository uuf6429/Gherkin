<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Filter;

use Behat\Gherkin\Filter\TagFilter;
use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioNode;
use Exception;
use PHPUnit\Framework\TestCase;

class TagFilterTest extends TestCase
{
    public function testFilterFeature()
    {
        $feature = new FeatureNode(null, null, ['wip'], null, [], null, null, null, 1);
        $filter = new TagFilter('@wip');
        $this->assertEquals($feature, $filter->filterFeature($feature));

        $scenarios = [
            new ScenarioNode(null, [], [], null, 2),
            $matchedScenario = new ScenarioNode(null, ['wip'], [], null, 4),
        ];
        $feature = new FeatureNode(null, null, [], null, $scenarios, null, null, null, 1);
        $filteredFeature = $filter->filterFeature($feature);

        $this->assertSame([$matchedScenario], $filteredFeature->getScenarios());

        $filter = new TagFilter('~@wip');
        $scenarios = [
            $matchedScenario = new ScenarioNode(null, [], [], null, 2),
            new ScenarioNode(null, ['wip'], [], null, 4),
        ];
        $feature = new FeatureNode(null, null, [], null, $scenarios, null, null, null, 1);
        $filteredFeature = $filter->filterFeature($feature);

        $this->assertSame([$matchedScenario], $filteredFeature->getScenarios());
    }

    public function testIsFeatureMatchFilter()
    {
        $feature = new FeatureNode(null, null, [], null, [], null, null, null, 1);

        $filter = new TagFilter('@wip');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $feature = new FeatureNode(null, null, ['wip'], null, [], null, null, null, 1);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new TagFilter('~@done');
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature = new FeatureNode(null, null, ['wip', 'done'], null, [], null, null, null, 1);
        $this->assertFalse($filter->isFeatureMatch($feature));

        $feature = new FeatureNode(null, null, ['tag1', 'tag2', 'tag3'], null, [], null, null, null, 1);
        $filter = new TagFilter('@tag5,@tag4,@tag6');
        $this->assertFalse($filter->isFeatureMatch($feature));

        $feature = new FeatureNode(null, null, [
            'tag1',
            'tag2',
            'tag3',
            'tag5',
        ], null, [], null, null, null, 1);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new TagFilter('@wip&&@vip');
        $feature = new FeatureNode(null, null, ['wip', 'done'], null, [], null, null, null, 1);
        $this->assertFalse($filter->isFeatureMatch($feature));

        $feature = new FeatureNode(null, null, ['wip', 'done', 'vip'], null, [], null, null, null, 1);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $filter = new TagFilter('@wip,@vip&&@user');
        $feature = new FeatureNode(null, null, ['wip'], null, [], null, null, null, 1);
        $this->assertFalse($filter->isFeatureMatch($feature));

        $feature = new FeatureNode(null, null, ['vip'], null, [], null, null, null, 1);
        $this->assertFalse($filter->isFeatureMatch($feature));

        $feature = new FeatureNode(null, null, ['wip', 'user'], null, [], null, null, null, 1);
        $this->assertTrue($filter->isFeatureMatch($feature));

        $feature = new FeatureNode(null, null, ['vip', 'user'], null, [], null, null, null, 1);
        $this->assertTrue($filter->isFeatureMatch($feature));
    }

    public function testIsScenarioMatchFilter()
    {
        $feature = new FeatureNode(null, null, ['feature-tag'], null, [], null, null, null, 1);
        $scenario = new ScenarioNode(null, [], [], null, 2);

        $filter = new TagFilter('@wip');
        $this->assertFalse($filter->isScenarioMatch($feature, $scenario));

        $filter = new TagFilter('~@done');
        $this->assertTrue($filter->isScenarioMatch($feature, $scenario));

        $scenario = new ScenarioNode(null, [
            'tag1',
            'tag2',
            'tag3',
        ], [], null, 2);
        $filter = new TagFilter('@tag5,@tag4,@tag6');
        $this->assertFalse($filter->isScenarioMatch($feature, $scenario));

        $scenario = new ScenarioNode(null, [
            'tag1',
            'tag2',
            'tag3',
            'tag5',
        ], [], null, 2);
        $this->assertTrue($filter->isScenarioMatch($feature, $scenario));

        $filter = new TagFilter('@wip&&@vip');
        $scenario = new ScenarioNode(null, ['wip', 'not-done'], [], null, 2);
        $this->assertFalse($filter->isScenarioMatch($feature, $scenario));

        $scenario = new ScenarioNode(null, [
            'wip',
            'not-done',
            'vip',
        ], [], null, 2);
        $this->assertTrue($filter->isScenarioMatch($feature, $scenario));

        $filter = new TagFilter('@wip,@vip&&@user');
        $scenario = new ScenarioNode(null, [
            'wip',
        ], [], null, 2);
        $this->assertFalse($filter->isScenarioMatch($feature, $scenario));

        $scenario = new ScenarioNode(null, ['vip'], [], null, 2);
        $this->assertFalse($filter->isScenarioMatch($feature, $scenario));

        $scenario = new ScenarioNode(null, ['wip', 'user'], [], null, 2);
        $this->assertTrue($filter->isScenarioMatch($feature, $scenario));

        $filter = new TagFilter('@feature-tag&&@user');
        $scenario = new ScenarioNode(null, ['wip', 'user'], [], null, 2);
        $this->assertTrue($filter->isScenarioMatch($feature, $scenario));

        $filter = new TagFilter('@feature-tag&&@user');
        $scenario = new ScenarioNode(null, ['wip'], [], null, 2);
        $this->assertFalse($filter->isScenarioMatch($feature, $scenario));

        $scenario = new OutlineNode(null, ['wip'], [], [
            new ExampleTableNode([], null, ['etag1', 'etag2']),
            new ExampleTableNode([], null, ['etag2', 'etag3']),
        ], null, 2);

        $tagFilter = new TagFilter('@etag3');
        $this->assertTrue($tagFilter->isScenarioMatch($feature, $scenario));

        $tagFilter = new TagFilter('~@etag3');
        $this->assertTrue($tagFilter->isScenarioMatch($feature, $scenario));

        $tagFilter = new TagFilter('@wip');
        $this->assertTrue($tagFilter->isScenarioMatch($feature, $scenario));

        $tagFilter = new TagFilter('@wip&&@etag3');
        $this->assertTrue($tagFilter->isScenarioMatch($feature, $scenario));

        $tagFilter = new TagFilter('@feature-tag&&@etag1&&@wip');
        $this->assertTrue($tagFilter->isScenarioMatch($feature, $scenario));

        $tagFilter = new TagFilter('@feature-tag&&~@etag11111&&@wip');
        $this->assertTrue($tagFilter->isScenarioMatch($feature, $scenario));

        $tagFilter = new TagFilter('@feature-tag&&~@etag1&&@wip');
        $this->assertTrue($tagFilter->isScenarioMatch($feature, $scenario));

        $tagFilter = new TagFilter('@feature-tag&&@etag2');
        $this->assertTrue($tagFilter->isScenarioMatch($feature, $scenario));

        $tagFilter = new TagFilter('~@etag1&&~@etag3');
        $this->assertFalse($tagFilter->isScenarioMatch($feature, $scenario));

        $tagFilter = new TagFilter('@etag1&&@etag3');
        $this->assertFalse($tagFilter->isScenarioMatch($feature, $scenario), 'Tags from different examples tables');
    }

    public function testFilterFeatureWithTaggedExamples()
    {
        $exampleTableNode1 = new ExampleTableNode([], null, ['etag1', 'etag2']);
        $exampleTableNode2 = new ExampleTableNode([], null, ['etag2', 'etag3']);
        $scenario = new OutlineNode(null, ['wip'], [], [
            $exampleTableNode1,
            $exampleTableNode2,
        ], null, 2);
        $feature = new FeatureNode(null, null, ['feature-tag'], null, [$scenario], null, null, null, 1);

        $tagFilter = new TagFilter('@etag2');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        $this->assertEquals($scenario, $scenarioInterfaces[0]);

        $tagFilter = new TagFilter('@etag1');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        /* @noinspection PhpUndefinedMethodInspection */
        $this->assertEquals([$exampleTableNode1], $scenarioInterfaces[0]->getExampleTables());

        $tagFilter = new TagFilter('~@etag3');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        /* @noinspection PhpUndefinedMethodInspection */
        $this->assertEquals([$exampleTableNode1], $scenarioInterfaces[0]->getExampleTables());

        $tagFilter = new TagFilter('@wip');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        $this->assertEquals($scenario, $scenarioInterfaces[0]);

        $tagFilter = new TagFilter('@wip&&@etag3');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        /* @noinspection PhpUndefinedMethodInspection */
        $this->assertEquals([$exampleTableNode2], $scenarioInterfaces[0]->getExampleTables());

        $tagFilter = new TagFilter('@feature-tag&&@etag1&&@wip');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        /* @noinspection PhpUndefinedMethodInspection */
        $this->assertEquals([$exampleTableNode1], $scenarioInterfaces[0]->getExampleTables());

        $tagFilter = new TagFilter('@feature-tag&&~@etag11111&&@wip');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        $this->assertEquals($scenario, $scenarioInterfaces[0]);

        $tagFilter = new TagFilter('@feature-tag&&~@etag1&&@wip');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        /* @noinspection PhpUndefinedMethodInspection */
        $this->assertEquals([$exampleTableNode2], $scenarioInterfaces[0]->getExampleTables());

        $tagFilter = new TagFilter('@feature-tag&&@etag2');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        $this->assertEquals($scenario, $scenarioInterfaces[0]);

        $exampleTableNode1 = new ExampleTableNode([], null, ['etag1', 'etag']);
        $exampleTableNode2 = new ExampleTableNode([], null, ['etag2', 'etag22', 'etag']);
        $exampleTableNode3 = new ExampleTableNode([], null, ['etag3', 'etag22', 'etag']);
        $exampleTableNode4 = new ExampleTableNode([], null, ['etag4', 'etag']);
        $scenario1 = new OutlineNode(null, ['wip'], [], [
            $exampleTableNode1,
            $exampleTableNode2,
        ], null, 2);
        $scenario2 = new OutlineNode(null, ['wip'], [], [
            $exampleTableNode3,
            $exampleTableNode4,
        ], null, 2);
        $feature = new FeatureNode(null, null, ['feature-tag'], null, [$scenario1, $scenario2], null, null, null, 1);

        $tagFilter = new TagFilter('@etag');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        $this->assertEquals([$scenario1, $scenario2], $scenarioInterfaces);

        $tagFilter = new TagFilter('@etag22');
        $matched = $tagFilter->filterFeature($feature);
        $scenarioInterfaces = $matched->getScenarios();
        $this->assertCount(2, $scenarioInterfaces);
        /* @noinspection PhpUndefinedMethodInspection */
        $this->assertEquals([$exampleTableNode2], $scenarioInterfaces[0]->getExampleTables());
        /* @noinspection PhpUndefinedMethodInspection */
        $this->assertEquals([$exampleTableNode3], $scenarioInterfaces[1]->getExampleTables());
    }

    public function testFilterWithWhitespaceIsDeprecated()
    {
        $this->expectDeprecationError();

        $tagFilter = new TagFilter('@tag with space');
        $scenario = new ScenarioNode(null, ['tag with space'], [], null, 2);
        $feature = new FeatureNode(null, null, [], null, [$scenario], null, null, null, 1);

        $scenarios = $tagFilter->filterFeature($feature)->getScenarios();

        $this->assertEquals([$scenario], $scenarios);
    }

    public function testTagFilterThatIsAllWhitespaceIsIgnored()
    {
        $feature = new FeatureNode(null, null, [], null, [], null, null, null, 1);
        $tagFilter = new TagFilter('');
        $result = $tagFilter->isFeatureMatch($feature);

        $this->assertTrue($result);
    }

    private function expectDeprecationError()
    {
        set_error_handler(
            static function ($errno, $errstr) {
                restore_error_handler();
                throw new Exception($errstr, $errno);
            },
            E_ALL
        );
        $this->expectException('Exception');
    }
}
