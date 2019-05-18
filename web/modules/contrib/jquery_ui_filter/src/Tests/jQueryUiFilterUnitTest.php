<?php

/**
 * @file
 * Definition of Drupal\jquery_ui_filter\Tests\jQueryUiFilterUnitTest.
 *
 * Copied from \Drupal\filter\Tests\FilterUnitTest.
 */

namespace Drupal\jquery_ui_filter\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\filter\FilterPluginCollection;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\Component\Utility\Html;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests jQuery UI filter.
 *
 * @group jQuery UI filter
 */
class jQueryUiFilterUnitTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'filter', 'jquery_ui_filter'];

  /**
   * A jQuery UI filter.
   *
   * @var \Drupal\jquery_ui_filter\Plugin\Filter\jQueryUiFilter
   */
  protected $filter;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['jquery_ui_filter']);

    // Reset HTML ids.
    Html::resetSeenIds();

    // Get \Drupal\jquery_ui_filter\Plugin\Filter\jQueryUiFilter object.
    $filter_bag = new FilterPluginCollection(\Drupal::service('plugin.manager.filter'), ['jquery_ui_filter']);
    $this->filter = $filter_bag->get('jquery_ui_filter');
  }

  /**
   * Tests the matching patterns.
   */
  public function testMatchingPatterns() {
    // NOTE: Accordion and tabs use the same code base so we are only testing
    // accordion widgets.
    $tests = [
      // Mixed case tags do nothing.
      '<p>[Accordion]</p>' => [
        '<p>[Accordion]</p>' => TRUE,
      ],
      // Wrapped in <div> tags.
      '<div>[accordion]</div><div>[/accordion]</div>' => [
        '<div data-ui-role="accordion"></div>' => TRUE,
      ],
      // Wrapped in <p> tags.
      '<p>[accordion]</p><p>[/accordion]</p>' => [
        '<div data-ui-role="accordion"></div>' => TRUE,
      ],
      // Using option.
      '<div>[accordion option]</div>' => [
        '<div data-ui-role="accordion" data-ui-option="true">' => TRUE,
      ],
      // Using double quotes.
      '<div>[accordion option="value"]</div>' => [
        '<div data-ui-role="accordion" data-ui-option="value">' => TRUE,
      ],
      // Using single quotes.
      '<div>[accordion option=\'value\']</div>' => [
        '<div data-ui-role="accordion" data-ui-option="value">' => TRUE,
      ],
      // Using custom 'camelCase' option .
      '<div>[accordion camelCase="value"]</div>' => [
        '<div data-ui-role="accordion" data-ui-camel-case="value">' => TRUE,
      ],
      '<div>[accordion camelCase]</div>' => [
        '<div data-ui-role="accordion" data-ui-camel-case="true">' => TRUE,
      ],
      // With with extra spaces.
      '<div>[accordion  option ]</div>' => [
        '<div data-ui-role="accordion" data-ui-option="true">' => TRUE,
      ],
      // Options with encoded characters.
      '<div>[accordion &nbsp;option=&quot;value&quot;]</div>' => [
        '<div data-ui-role="accordion" data-ui-option="value">' => TRUE,
      ],
      // Options with bad character characters.
      '<div>[accordion < &lt;&nbsp;option=&quot;value&quot;]</div>' => [
        '<div data-ui-role="accordion" data-ui-option="value">' => TRUE,
      ],
      // Using JSON in option.
      '<div>[accordion json=\'{"name":"value"}\']</div>' => [
        '<div data-ui-role="accordion" data-ui-json="{&quot;name&quot;:&quot;value&quot;}">' => TRUE,
      ],
    ];
    $this->assertFilteredString($this->filter, $tests);
  }

  /****************************************************************************/
  //  Copied from: Drupal\filter\Tests\FilterUnitTest
  /****************************************************************************/

  /**
   * Asserts multiple filter output expectations for multiple input strings.
   *
   * @param FilterInterface $filter
   *   A input filter object.
   * @param array $tests
   *   An associative array, whereas each key is an arbitrary input string and
   *   each value is again an associative array whose keys are filter output
   *   strings and whose values are Booleans indicating whether the output is
   *   expected or not.
   *
   * For example:
   * @code
   * $tests = array(
   *   'Input string' => array(
   *     '<p>Input string</p>' => TRUE,
   *     'Input string<br' => FALSE,
   *   ),
   * );
   * @endcode
   */
  protected function assertFilteredString(FilterInterface $filter, array $tests) {
    foreach ($tests as $source => $tasks) {
      $result = $filter->process($source, $filter)->getProcessedText();
      foreach ($tasks as $value => $is_expected) {
        // Not using assertIdentical, since combination with strpos() is hard to grok.
        if ($is_expected) {
          $success = $this->assertTrue(strpos($result, $value) !== FALSE, new FormattableMarkup('@source: @value found. Filtered result: @result.', [
            '@source' => var_export($source, TRUE),
            '@value' => var_export($value, TRUE),
            '@result' => var_export($result, TRUE),
          ]));
        }
        else {
          $success = $this->assertTrue(strpos($result, $value) === FALSE, new FormattableMarkup('@source: @value not found. Filtered result: @result.', [
            '@source' => var_export($source, TRUE),
            '@value' => var_export($value, TRUE),
            '@result' => var_export($result, TRUE),
          ]));
        }
        if (!$success) {
          $this->verbose('Source:<pre>' . Html::escape(var_export($source, TRUE)) . '</pre>'
            . '<hr />' . 'Result:<pre>' . Html::escape(var_export($result, TRUE)) . '</pre>'
            . '<hr />' . ($is_expected ? 'Expected:' : 'Not expected:')
            . '<pre>' . Html::escape(var_export($value, TRUE)) . '</pre>'
          );
        }
      }
    }
  }

}
