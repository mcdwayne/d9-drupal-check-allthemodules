<?php

namespace Drupal\no_nbsp\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * @file
 * Tests for the no_nbsp.module.
 */

/**
 * Base class for the no non-breaking space filter tests.
 *
 * Some helper methods.
 */
abstract class NoNbspWebTestBase extends WebTestBase {

  /**
   * Create a new text format.
   *
   * Create a new text format with an enabled no non-breaking space filter
   * programmatically.
   *
   * @param string $name
   *   The machine name of the new text format.
   * @param bool $status
   *   If the filter is enabled or not.
   */
  protected function createTextFormatProgrammatically($name, $status) {
    $format = [
      'format' => $name,
      'name' => $name,
      'filters' => [
        'filter_no_nbsp' => [
          'status' => $status,
        ],
      ],
    ];
    $format = (object) $format;
    filter_format_save($format);
    return $format;
  }

  /**
   * Create a new text format.
   *
   * Create a new text format with an enabled no non-breaking space filter
   * using the web functions provided by simpletest.
   *
   * @param string $name
   *   The machine name of the new text format.
   * @param bool $status
   *   If the filter is enabled or not.
   */
  protected function createTextFormatWeb($name, $status) {
    $edit = [
      'format' => $name,
      'name' => $name,
      'roles[anonymous]' => 1,
      'roles[authenticated]' => 1,
    ];
    if ($status) {
      $edit['filters[filter_no_nbsp][status]'] = $status;
    }
    $this->drupalPostForm('admin/config/content/formats/add', $edit, t('Save configuration'));
    filter_formats_reset();
    $formats = filter_formats();
    return $formats[$name];
  }

  /**
   * Create a new text format and a new node.
   *
   * @param string $text
   *   Body text of the node.
   * @param bool $status
   *   If the filter is enabled or not.
   */
  protected function createFormatAndNode($text, $status) {
    $format = $this->createTextFormatWeb(strtolower($this->randomMachineName()), $status);
    filter_formats_reset();
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName();
    $edit['body[0][value]'] = $text;
    $edit['body[0][format]'] = $format->get('name');
    $this->drupalPostForm('node/add/page', $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->drupalGet('node/' . $node->id());
    return $node;
  }

}
