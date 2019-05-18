<?php

namespace Drupal\more_filters\Tests;

use Drupal\Core\Render\RenderContext;
use Drupal\filter\FilterPluginCollection;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the Phone Format filter.
 *
 * Adapted from core Filter module tests.
 *
 * @group filter
 */
class FilterPhoneFormatTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('filter', 'more_filters');

  /**
   * Stores all available filter plugins.
   *
   * @var \Drupal\filter\Plugin\FilterInterface[]
   */
  protected $filters = array();

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Get all available Filter plugins from the plugin manager.
    $plugin_manager = $this->container->get('plugin.manager.filter');
    $bag = new FilterPluginCollection($plugin_manager, $this->filters);
    $this->filters = $bag->getAll();
  }

  /**
   * Tests the Phone Format filter plugin.
   */
  public function testOrdinalsFilter() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $lang_code = 'und';

    // Get the Phone Format filter.
    $filter = $this->filters['filter_phone_format'];

    // Define anonymous test function to execute the filter plugin's
    // process() method in a render context.
    $test = function ($input) use ($filter, $renderer, $lang_code) {
      return $renderer->executeInRenderContext(new RenderContext(), function () use ($input, $filter, $lang_code) {
        return $filter->process($input, $lang_code);
      });
    };

    // Define text for testing the filter.
    $input = 'The first phone number is 503.233.7878. Another phone number is 503 . 555 . 1212.';
    $expected = 'The first phone number is (503) 233-7878. Another phone number is (503) 555-1212.';

    // Apply the filter to the test text.
    $text_filtered = $test($input)->getProcessedText();
    // The filtered input text should match the expected text.
    $this->assertIdentical($expected, $text_filtered);
  }

}
