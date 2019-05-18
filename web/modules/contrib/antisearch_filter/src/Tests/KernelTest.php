<?php

namespace Drupal\antisearch_filter\Tests;

use Drupal\simpletest\KernelTestBase;
use Drupal\antisearch_filter\Plugin\Filter\FilterAntisearch;

/**
 * Run unit tests (simpletest kernel tests) on some functions and methods.
 *
 * @group antisearch_filter
 */
class KernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['antisearch_filter'];

  /**
   * Test the function “antisearch_filter”.
   */
  public function testFunctionAntisearchFilter() {
    $result = antisearch_filter('lol');
    $this->assertEqual(strlen($result), 156, 'Lenght of the dismembered string');
    $this->assertTrue(strpos($result, 'antisearch-filter'), 'Contains the class name “antisearch-filter”');
    $this->assertTrue(strpos($result, '<i>'), 'Contains &lt;i&gt;');
    $this->assertTrue(strpos($result, '</i>'), 'Contains &lt;/i&gt;');
  }

  /**
   * Test the function “antisearch_filter”.
   */
  public function testFunctionAntisearchFilterDismemberer() {
    $result = _antisearch_filter_dismemberer('lol');
    $this->assertEqual(strlen($result), 27);
    $this->assertTrue(strpos($result, '<i>'));
    $this->assertTrue(strpos($result, '</i>'));

    // Simple example.
    $this->assertEqual(_antisearch_filter_dismemberer('lol', ['i']), 'l<i>i</i>o<i>i</i>l<i>i</i>');

    // One character.
    $this->assertEqual(_antisearch_filter_dismemberer('l', ['i']), 'l<i>i</i>');

    // Empty string.
    $this->assertEqual(_antisearch_filter_dismemberer('', ['i']), '');

    // German umlaut.
    $this->assertEqual(_antisearch_filter_dismemberer('ö', ['i']), '&ouml;<i>i</i>');

    // Strip tags.
    $this->assertEqual(_antisearch_filter_dismemberer('<i>l</i>', ['i']), 'l<i>i</i>');
  }

  /**
   * Test the method “tips()”.
   */
  public function testFunctionAntisearchFilterTips() {
    $filter = new FilterAntisearch([], TRUE, TRUE);
    $filter->settings = [
      'antisearch_filter_email' => TRUE,
      'antisearch_filter_strike' => TRUE,
      'antisearch_filter_bracket' => TRUE,
    ];
    $result = $filter->tips();
    $this->assertEqual($result, 'The antisearch filter will be applied to e-mail addresses, to any text surrounded by HTML strike tags, to any text surrounded by square brackets.', 'All settings');

    $filter->settings['antisearch_filter_bracket'] = FALSE;
    $result = $filter->tips();
    $this->assertEqual($result, 'The antisearch filter will be applied to e-mail addresses, to any text surrounded by HTML strike tags.', 'Without brackets');

    $filter->settings['antisearch_filter_strike'] = FALSE;
    $result = $filter->tips();
    $this->assertEqual($result, 'The antisearch filter will be applied to e-mail addresses.', 'Without strike');

    $filter->settings['antisearch_filter_email'] = FALSE;
    $result = $filter->tips();
    $this->assertEqual($result, 'The antisearch filter will be applied .', 'Without email');
  }

}
