<?php

/*
 * This file is part of the Behat Gherkin Parser.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Behat\Gherkin;

use Behat\Gherkin\Gherkin;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;
use PHPUnit\Framework\TestCase;

class GherkinTest extends TestCase
{
    public function testLoader()
    {
        $customFilter1 = $this->getCustomFilterMock();
        $customFilter2 = $this->getCustomFilterMock();

        $gherkin = new Gherkin();
        $gherkin->addLoader($loader = $this->getLoaderMock());
        $gherkin->addFilter($nameFilter = $this->getNameFilterMock());
        $gherkin->addFilter($tagFilter = $this->getTagFilterMock());

        $scenario = new ScenarioNode(null, [], [], null, null);
        $feature = new FeatureNode(null, null, [], null, [$scenario], null, null, null, null);

        $loader
            ->expects($this->once())
            ->method('supports')
            ->with($resource = 'some/feature/resource')
            ->will($this->returnValue(true));
        $loader
            ->expects($this->once())
            ->method('load')
            ->with($resource)
            ->will($this->returnValue([$feature]));

        $nameFilter
            ->expects($this->once())
            ->method('filterFeature')
            ->with($this->identicalTo($feature))
            ->will($this->returnValue($feature));
        $tagFilter
            ->expects($this->once())
            ->method('filterFeature')
            ->with($this->identicalTo($feature))
            ->will($this->returnValue($feature));
        $customFilter1
            ->expects($this->once())
            ->method('filterFeature')
            ->with($this->identicalTo($feature))
            ->will($this->returnValue($feature));
        $customFilter2
            ->expects($this->once())
            ->method('filterFeature')
            ->with($this->identicalTo($feature))
            ->will($this->returnValue($feature));

        $features = $gherkin->load($resource, [$customFilter1, $customFilter2]);
        $this->assertCount(1, $features);

        $scenarios = $features[0]->getScenarios();
        $this->assertCount(1, $scenarios);
        $this->assertSame($scenario, $scenarios[0]);
    }

    public function testNotFoundLoader()
    {
        $gherkin = new Gherkin();

        $this->assertEquals([], $gherkin->load('some/feature/resource'));
    }

    public function testLoaderFiltersFeatures()
    {
        $gherkin = new Gherkin();
        $gherkin->addLoader($loader = $this->getLoaderMock());
        $gherkin->addFilter($nameFilter = $this->getNameFilterMock());

        $feature = new FeatureNode(null, null, [], null, [], null, null, null, null);

        $loader
            ->expects($this->once())
            ->method('supports')
            ->with($resource = 'some/feature/resource')
            ->will($this->returnValue(true));
        $loader
            ->expects($this->once())
            ->method('load')
            ->with($resource)
            ->will($this->returnValue([$feature]));

        $nameFilter
            ->expects($this->once())
            ->method('filterFeature')
            ->with($this->identicalTo($feature))
            ->will($this->returnValue($feature));
        $nameFilter
            ->expects($this->once())
            ->method('isFeatureMatch')
            ->with($this->identicalTo($feature))
            ->will($this->returnValue(false));

        $features = $gherkin->load($resource);
        $this->assertCount(0, $features);
    }

    public function testSetFiltersOverridesAllFilters()
    {
        $gherkin = new Gherkin();
        $gherkin->addLoader($loader = $this->getLoaderMock());
        $gherkin->addFilter($nameFilter = $this->getNameFilterMock());
        $gherkin->setFilters([]);

        $feature = new FeatureNode(null, null, [], null, [], null, null, null, null);

        $loader
            ->expects($this->once())
            ->method('supports')
            ->with($resource = 'some/feature/resource')
            ->will($this->returnValue(true));
        $loader
            ->expects($this->once())
            ->method('load')
            ->with($resource)
            ->will($this->returnValue([$feature]));

        $nameFilter
            ->expects($this->never())
            ->method('filterFeature');
        $nameFilter
            ->expects($this->never())
            ->method('isFeatureMatch');

        $features = $gherkin->load($resource);
        $this->assertCount(1, $features);
    }

    public function testSetBasePath()
    {
        $gherkin = new Gherkin();
        $gherkin->addLoader($loader1 = $this->getLoaderMock());
        $gherkin->addLoader($loader2 = $this->getLoaderMock());

        $loader1
            ->expects($this->once())
            ->method('setBasePath')
            ->with($basePath = '/base/path')
            ->will($this->returnValue(null));

        $loader2
            ->expects($this->once())
            ->method('setBasePath')
            ->with($basePath = '/base/path')
            ->will($this->returnValue(null));

        $gherkin->setBasePath($basePath);
    }

    protected function getLoaderMock()
    {
        return $this->getMockBuilder('Behat\Gherkin\Loader\GherkinFileLoader')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getCustomFilterMock()
    {
        return $this->getMockBuilder('Behat\Gherkin\Filter\FilterInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getNameFilterMock()
    {
        return $this->getMockBuilder('Behat\Gherkin\Filter\NameFilter')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getTagFilterMock()
    {
        return $this->getMockBuilder('Behat\Gherkin\Filter\TagFilter')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
