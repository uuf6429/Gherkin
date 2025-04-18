<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin\Node;

use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Node\TableNode;
use PHPUnit\Framework\TestCase;

class ExampleNodeTest extends TestCase
{
    public function testCreateExampleSteps(): void
    {
        $steps = [
            new StepNode('Gangway!', 'I am <name>', [], 1, 'Given'),
            new StepNode('Aye!', 'my email is <email>', [], 1, 'And'),
            new StepNode('Blimey!', 'I open homepage', [], 1, 'When'),
            new StepNode('Let go and haul', 'website should recognise me', [], 1, 'Then'),
        ];

        $table = new ExampleTableNode([
            ['name', 'email'],
            ['everzet', 'ever.zet@gmail.com'],
            ['example', 'example@example.com'],
        ], 'Examples');

        $outline = new OutlineNode(null, [], $steps, $table, '', 1);
        $examples = $outline->getExamples();

        $this->assertCount(4, $steps = $examples[0]->getSteps());

        $this->assertEquals('Gangway!', $steps[0]->getType());
        $this->assertEquals('Gangway!', $steps[0]->getKeyword());
        $this->assertEquals('Given', $steps[0]->getKeywordType());
        $this->assertEquals('I am everzet', $steps[0]->getText());
        $this->assertEquals('Aye!', $steps[1]->getType());
        $this->assertEquals('Aye!', $steps[1]->getKeyword());
        $this->assertEquals('And', $steps[1]->getKeywordType());
        $this->assertEquals('my email is ever.zet@gmail.com', $steps[1]->getText());
        $this->assertEquals('Blimey!', $steps[2]->getType());
        $this->assertEquals('Blimey!', $steps[2]->getKeyword());
        $this->assertEquals('When', $steps[2]->getKeywordType());
        $this->assertEquals('I open homepage', $steps[2]->getText());

        $this->assertCount(4, $steps = $examples[1]->getSteps());

        $this->assertEquals('Gangway!', $steps[0]->getType());
        $this->assertEquals('Gangway!', $steps[0]->getKeyword());
        $this->assertEquals('Given', $steps[0]->getKeywordType());
        $this->assertEquals('I am example', $steps[0]->getText());
        $this->assertEquals('Aye!', $steps[1]->getType());
        $this->assertEquals('Aye!', $steps[1]->getKeyword());
        $this->assertEquals('And', $steps[1]->getKeywordType());
        $this->assertEquals('my email is example@example.com', $steps[1]->getText());
        $this->assertEquals('Blimey!', $steps[2]->getType());
        $this->assertEquals('Blimey!', $steps[2]->getKeyword());
        $this->assertEquals('When', $steps[2]->getKeywordType());
        $this->assertEquals('I open homepage', $steps[2]->getText());
    }

    public function testCreateExampleStepsWithArguments(): void
    {
        $steps = [
            new StepNode('Gangway!', 'I am <name>', [], 1, 'Given'),
            new StepNode('Aye!', 'my email is <email>', [], 1, 'And'),
            new StepNode(
                'Blimey!',
                'I open:',
                [
                    new PyStringNode(['page: <url>'], 1),
                ],
                1,
                'When'
            ),
            new StepNode(
                'Let go and haul',
                'website should recognise me',
                [
                    new TableNode([['page', '<url>']]),
                ],
                1,
                'Then'
            ),
        ];

        $table = new ExampleTableNode([
            ['name', 'email', 'url'],
            ['everzet', 'ever.zet@gmail.com', 'homepage'],
            ['example', 'example@example.com', 'other page'],
        ], 'Examples');

        $outline = new OutlineNode(null, [], $steps, $table, '', 1);
        $examples = $outline->getExamples();

        $steps = $examples[0]->getSteps();

        $args = $steps[2]->getArguments();
        $this->assertInstanceOf(PyStringNode::class, $args[0]);
        $this->assertEquals('page: homepage', $args[0]->getRaw());

        $args = $steps[3]->getArguments();
        $this->assertInstanceOf(TableNode::class, $args[0]);
        $this->assertEquals('| page | homepage |', $args[0]->getTableAsString());
    }
}
