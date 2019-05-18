<?php

/**
 * @file
 * Definition of Drupal\aloha\Tests\FormatBlackboxTest.
 */

namespace Drupal\aloha\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests aloha compatibility black box tests.
 */
class FormatBlackboxTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('filter', 'aloha');

  public static function getInfo() {
    return array(
      'name' => 'Aloha filter black box testing',
      'description' => 'Tests aloha compatibility black box tests.',
      'group' => 'Aloha',
    );
  }

  /**
   * Enable filter module for the tests.
   */
  function setUp() {
    parent::setUp();
    $this->enableModules(array('filter'));
  }

  /**
   * Test black box tested with sample text formats.
   */
  function testBlackbox() {
    // Create Full HTML format.
    $full_html_format = array(
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 1,
      'filters' => array(
        'filter_htmlcorrector' => array(
          'weight' => 10,
          'status' => 1,
        ),
      ),
    );
    $full_html_format = (object) $full_html_format;
    filter_format_save($full_html_format);

    $full_allowed = array('p', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'pre', 'a', 'em', 'strong', 'i', 'b', 'u', 'cite', 'q', 'br', 'code', 'img');
    $allowed = aloha_get_allowed_html_by_format('full_html');
    $this->assertEqual(array_keys($allowed), $full_allowed);

    // Create a limited HTML format.
    $filtered_html_format = array(
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 2,
      'filters' => array(
        'filter_htmlcorrector' => array(
          'weight' => 10,
          'status' => 1,
        ),
        'filter_html' => array(
          'weight' => 8,
          'status' => 1,
          'settings' => array(
            // Need to allow <p> because inline elements are tested with <p>.
            // If there is no <p> allowed, inline elements are gone.
            'allowed_html' => '<a> <em> <strong> <cite> <p> <h3>',
            'filter_html_help' => 1,
            'filter_html_nofollow' => 0,
          ),
        ),
      ),
    );
    $filtered_html_format = (object) $filtered_html_format;
    filter_format_save($filtered_html_format);

    $filtered_allowed = array('p', 'h3', 'a', 'em', 'strong', 'cite');
    $allowed = aloha_get_allowed_html_by_format('filtered_html');
    $this->assertEqual(array_keys($allowed), $filtered_allowed);

    // Create a limited HTML format with automatic paragraphs.
    $autoped_html_format = array(
      'format' => 'autoped_html',
      'name' => 'Autoped HTML',
      'weight' => 2,
      'filters' => array(
        'filter_autop' => array(
          'weight' => 10,
          'status' => 1,
        ),
        'filter_html' => array(
          'weight' => 8,
          'status' => 1,
          'settings' => array(
            // No need to allow <p> because filter_autop will add it in.
            'allowed_html' => '<a> <em> <strong> <cite> <h3>',
            'filter_html_help' => 1,
            'filter_html_nofollow' => 0,
          ),
        ),
      ),
    );
    $autoped_html_format = (object) $autoped_html_format;
    filter_format_save($autoped_html_format);

    $filtered_allowed = array('h3', 'a', 'em', 'strong', 'cite');
    $allowed = aloha_get_allowed_html_by_format('autoped_html');
    $this->assertEqual(array_keys($allowed), $filtered_allowed);
  }
}
