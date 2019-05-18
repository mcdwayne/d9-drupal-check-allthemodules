<?php

namespace Drupal\more_filters\Tests;

use Drupal\Core\Render\RenderContext;
use Drupal\filter\FilterPluginCollection;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the Ordinals filter.
 *
 * Adapted from core Filter module tests.
 *
 * @group filter
 */
class FilterOrdinalsTest extends WebTestBase {

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
   * Tests the Ordinals filter plugin.
   */
  public function testOrdinalsFilter() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $lang_code = 'und';

    // Get the Ordinals filter.
    $filter = $this->filters['filter_ordinals'];

    // Define anonymous test function to execute the filter plugin's
    // process() method in a render context.
    $test = function ($input) use ($filter, $renderer, $lang_code) {
      return $renderer->executeInRenderContext(new RenderContext(), function () use ($input, $filter, $lang_code) {
        return $filter->process($input, $lang_code);
      });
    };

    // Define text for testing the filter.
    $input = 'Here is the 1st item, the 2nd item, the 3rd item, and the 4th item.';
    $expected = 'Here is the 1<span class="ordinal">st</span> item, the 2<span class="ordinal">nd</span> item';
    $expected .= ', the 3<span class="ordinal">rd</span> item, and the 4<span class="ordinal">th</span> item.';

    // Apply the filter to the test text.
    $text_filtered = $test($input)->getProcessedText();
    // @todo
    // Add tests for all filter settings too (for instance, "use <sup> tag
    // instead of <span> tag").
    $this->assertIdentical($expected, $text_filtered);
  }

}
