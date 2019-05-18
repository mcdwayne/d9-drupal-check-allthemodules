<?php

namespace Drupal\Tests\dcat_import\Unit\Plugin;

use Drupal\Tests\UnitTestCase;
use Drupal\dcat_import\Plugin\DcatGraph;
use EasyRdf_Resource;
use EasyRdf_Http_Exception;

/**
 * @coversDefaultClass \Drupal\dcat_import\Plugin\DcatGraph
 * @group dcat_import
 */
class DcatGraphTest extends UnitTestCase {

  /**
   * Return a mocked DcatGraph where only the given method is not mocked.
   *
   * @param string $method
   *   The method to not mock.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   *   The mocked DcatGraph class.
   */
  private function mockedGraph($method) {
    $methods = get_class_methods('Drupal\dcat_import\Plugin\DcatGraph');
    unset($methods[array_search($method, $methods)]);

    return $this->getMockBuilder('Drupal\dcat_import\Plugin\DcatGraph')
      ->disableOriginalConstructor()
      ->setMethods($methods)
      ->getMock();
  }

  /**
   * Test getPagerArgument().
   */
  public function testPagerArgument() {
    $graph = $this->mockedGraph('getPagerArgument');
    $argument = $this->randomMachineName();
    $graph->pagerArgument = $argument;
    $this->assertSame($argument, $graph->getPagerArgument());
  }

  /**
   * Test compareResults().
   *
   * @dataProvider providerCompareResults
   */
  public function testCompareResults($expected_result, $previous, $current) {
    $graph = $this->mockedGraph('compareResults');
    $this->assertSame($expected_result, $graph->compareResults($previous, $current));
  }

  /**
   * Provides data and expected results for the test method.
   *
   * @return array
   *   Data and expected results.
   */
  public function providerCompareResults() {
    $rescource_a = new EasyRdf_Resource('http://example.com/A');
    $rescource_b = new EasyRdf_Resource('http://example.com/B');

    return [
      [TRUE, [$rescource_a], [$rescource_a]],
      [FALSE, [$rescource_a], [$rescource_b]],
      [
        FALSE, [
          $rescource_a,
          $rescource_a,
        ], [
          $rescource_a,
          $rescource_b,
        ],
      ],
    ];
  }

  /**
   * Test getNoneBlankResources().
   *
   * @dataProvider providerGetNoneBlankResources
   */
  public function testGetNoneBlankResources($expected_result, $resources) {
    $graph = $this->mockedGraph('getNoneBlankResources');
    $this->assertSame($expected_result, $graph->getNoneBlankResources($resources));
  }

  /**
   * Provides data and expected results for the test method.
   *
   * @return array
   *   Data and expected results.
   */
  public function providerGetNoneBlankResources() {
    $graph = new DcatGraph();
    $graph->add('http://example.com', 'dc:title', 'Title of Page');
    $graph->add('http://example.com/empty', 'dc:title', 'Title of Page');

    $resource_empty = $graph->newBNode();
    $resource_notype = $graph->resource('http://example.com/empty');

    $resource = $graph->resource('http://example.com');
    $resource->setType('foaf:Person');

    return [
      [[], [$resource_empty, $resource_notype]],
      [[$resource], [$resource]],
      [[$resource], [$resource, $resource_empty, $resource_notype]],
    ];
  }

  /**
   * Test pagedUrlBuilder().
   *
   * @dataProvider providerPagedUrlBuilder
   */
  public function testPagedUrlBuilder($expected_result, $base, $argument, $count) {
    $graph = $this->mockedGraph('pagedUrlBuilder');
    $this->assertSame($expected_result, $graph->pagedUrlBuilder($base, $argument, $count));
  }

  /**
   * Provides data and expected results for the test method.
   *
   * @return array
   *   Data and expected results.
   */
  public function providerPagedUrlBuilder() {
    return [
      [NULL, NULL, 'page', 2],
      ['http://example.com', 'http://example.com', 'page', 1],
      ['http://example.com?page=2', 'http://example.com', 'page', 2],
      ['http://example.com?t=1&page=2', 'http://example.com?t=1', 'page', 2],
    ];
  }

  /**
   * Test load() no data test.
   */
  public function testLoadBlank() {
    $graph = $this->mockedGraph('load');

    $this->assertSame(0, $graph->load());
  }

  /**
   * Test load() one page two results.
   */
  public function testLoadSinglePage() {
    $graph = $this->mockedGraph('load');

    $graph->expects($this->exactly(3))
      ->method('getNoneBlankResources')
      ->will($this->returnValue(['', '']));
    $graph->expects($this->once())
      ->method('compareResults')
      ->will($this->returnValue(TRUE));
    $this->assertSame(2, $graph->load());
  }

  /**
   * Test load() two pages, two results per page.
   */
  public function testLoadTwoPages() {
    $graph = $this->mockedGraph('load');

    $graph->expects($this->exactly(4))
      ->method('getNoneBlankResources')
      ->will($this->onConsecutiveCalls(
        ['a', 'a'],
        ['a', 'a', 'b', 'b'],
        ['a', 'a', 'b', 'b'],
        ['a', 'a', 'b', 'b']
      ));
    $graph->expects($this->exactly(2))
      ->method('compareResults')
      ->will($this->onConsecutiveCalls(
        FALSE,
        TRUE
      ));
    $this->assertSame(4, $graph->load());
  }

  /**
   * Test load() two pages, two results per page + 404 on page 3.
   */
  public function testLoadTwoPages404() {
    $graph = $this->mockedGraph('load');

    $graph->expects($this->exactly(3))
      ->method('getNoneBlankResources')
      ->will($this->onConsecutiveCalls(
        ['a', 'a'],
        ['a', 'a', 'b', 'b'],
        ['a', 'a', 'b', 'b']
      ));
    $graph->expects($this->once())
      ->method('compareResults')
      ->will($this->returnValue(FALSE));
    $graph->expects($this->exactly(3))
      ->method('loadSingle')
      ->will($this->returnCallback(function () {
        static $count = 0;
        $count++;
        if ($count == 3) {
          throw new EasyRdf_Http_Exception('404 Test', 404);
        }
      }));
    $this->assertSame(4, $graph->load());
  }

  /**
   * Test load() exceptions other than EasyRdf_Http_Exception are still thrown.
   *
   * @expectedException \Exception
   */
  public function testLoadException() {
    $graph = $this->mockedGraph('load');

    $graph->expects($this->once())
      ->method('loadSingle')
      ->will($this->returnCallback(function() {
        throw new \Exception('Exception');
      }));
    $graph->load();
  }

  /**
   * Test load() EasyRdf_Http_Exception 404 should throw when there is no data.
   *
   * @expectedException EasyRdf_Http_Exception
   */
  public function testLoadException404() {
    $graph = $this->mockedGraph('load');

    $graph->expects($this->once())
      ->method('loadSingle')
      ->will($this->returnCallback(function() {
        throw new EasyRdf_Http_Exception('404 Test', 404);
      }));
    $graph->load();
  }

  /**
   * Test load() only EasyRdf_Http_Exception 404 should be catched.
   *
   * @expectedException EasyRdf_Http_Exception
   */
  public function testLoadException500() {
    $graph = $this->mockedGraph('load');

    $graph->expects($this->once())
      ->method('loadSingle')
      ->will($this->returnCallback(function() {
        throw new EasyRdf_Http_Exception('500 Test', 500);
      }));
    $graph->load();
  }

}
