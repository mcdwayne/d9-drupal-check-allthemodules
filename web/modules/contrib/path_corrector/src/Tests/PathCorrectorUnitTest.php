<?php

/**
 * @file
 * Definition of Drupal\path_corrector\Tests\PathCorrectorUnitTest.
 */

namespace Drupal\path_corrector\Tests;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\String;
use Drupal\filter\FilterPluginCollection;
use Drupal\path_corrector\Plugin\Filter;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests Filter module filters individually.
 *
 * @group filter
 */
class PathCorrectorUnitTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'filter', 'path_corrector');

  /**
   * @var \Drupal\filter\Plugin\FilterInterface[]
   */
  protected $filters;

  protected function setUp() {
    parent::setUp();
    $this->installConfig(array('system'));

    $manager = $this->container->get('plugin.manager.filter');
    $bag = new FilterPluginCollection($manager, array());
    $this->filters = $bag->getAll();
  }

  /**
   * Tests the align filter.
   */
  function testAlignFilter() {
    $filter = $this->filters['path_corrector'];

    $test = function ($input) use ($filter) {
      return $filter->process($input, 'und');
    };

    // Image SRC - no change.
    $input = '<img src="http://example.co.uk/test.jpg" />';
    $expected = $input;
    $this->assertIdentical($expected, $test($input)->getProcessedText());

    // Image SRC - change.
    $input    = '<img src="http://example.com/test.jpg" />';
    $expected = '<img src="http://example.org/test.jpg" />';
    $this->assertIdentical($expected, $test($input)->getProcessedText());

    // Hyperlink HREF - no change.
    $input = '<a href="http://example.co.uk/test" />';
    $expected = $input;
    $this->assertIdentical($expected, $test($input)->getProcessedText());

    // Hyperlink HREF - change.
    $input    = '<a href="http://example.com/test" />';
    $expected = '<a href="http://example.org/test" />';
    $this->assertIdentical($expected, $test($input)->getProcessedText());
  }
}
