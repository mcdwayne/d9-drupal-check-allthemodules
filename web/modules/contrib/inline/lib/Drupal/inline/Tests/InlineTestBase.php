<?php

/**
 * @file
 * Contains Drupal\inline\Tests\InlineTestBase.
 */

namespace Drupal\inline\Tests;

use Drupal\simpletest\WebTestBase;

abstract class InlineTestBase extends WebTestBase {

  public static $modules = array('inline', 'node');

  function setUp() {
    parent::setUp();

    // Setup Filtered HTML text format.
    $filtered_html_format = array(
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'filters' => array(
        'filter_html' => array(
          'status' => 1,
          'settings' => array(
            'allowed_html' => '<a>',
          ),
        ),
        'filter_autop' => array(
          'status' => 1,
        ),
        'filter_url' => array(
          'status' => 1,
        ),
      ),
    );
    $this->formats['filtered_html'] = (object) $filtered_html_format;
    filter_format_save($this->formats['filtered_html']);
    // drupalCreateContentType() will reset permissions already.
    //$this->checkPermissions(array(), TRUE);

    // Create Basic page and Article node types.
    //$this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));

    // Setup a second text field and instance.
    $this->secondFieldName = 'field_second';
    $this->secondField = array(
      'field_name' => $this->secondFieldName,
      'type' => 'text_with_summary',
    );
    $this->secondField = field_create_field($this->secondField);
    $this->secondInstance = array(
      'field_name' => $this->secondFieldName,
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Second field',
      'widget' => array(
        'type' => 'text_textarea_with_summary',
      ),
      'settings' => array(
        'display_summary' => TRUE,
      ),
      'display' => array(
        'default' => array(
          'label' => 'hidden',
          'type' => 'text_default',
        ),
        'teaser' => array(
          'label' => 'hidden',
          'type' => 'text_summary_or_trimmed',
        ),
      ),
    );
    $this->secondInstance = field_create_instance($this->secondInstance);
  }

  /**
   * Constructs a new macro tag of a given type and parameters.
   *
   * Tests should always use this helper function to make a potential future
   * macro syntax change possible.
   */
  function inlineBuildMacro($type, array $params) {
    $implementations = inline_get_info();
    if (!isset($implementations[$type])) {
      throw new \InvalidArgumentException(t('Unknown macro type %type.', array('%type' => $type)));
    }
    $macro = new $implementations[$type]['class']();
    $macro->params = $params;
    $serialized = inline_macro_serialize($macro);
    $this->pass(format_string('Created macro: @serialized', array('@serialized' => $serialized)), 'Inline');
    return $serialized;
  }

  /**
   * Creates a new node.
   *
   * @param mixed $text
   *   For most cases, a simple string to use as the node's body value.
   *   Alternatively, an array specifying the field $item; e.g.,
   *   @code
   *   array('summary' => 'Foo', 'value' => 'Bar')
   *   @endcode
   *   In any case, default values for 'value' and 'format' are added, unless
   *   specified.
   * @param array $settings
   *   See DrupalWebTestCase::drupalCreateNode().
   */
  function inlineCreateNode($text, array $settings = array()) {
    $settings += array(
      'type' => 'article',
      'promote' => 1,
      'language' => LANGUAGE_NOT_SPECIFIED,
    );
    $body = is_array($text) ? $text : array();
    $body += array(
      'value' => !is_array($text) ? $text : '',
      'format' => filter_default_format(),
    );
    $settings['body'][$settings['language']][0] = $body;
    $node = $this->drupalCreateNode($settings);
    return $node;
  }

  /**
   * Updates a node.
   *
   * @param Node $node
   *   The node object to update.
   * @param mixed $text
   *   For most cases, a simple string to use as the node's body value.
   *   Alternatively, an array specifying the field $item; e.g.,
   *   @code
   *   array('summary' => 'Foo', 'value' => 'Bar')
   *   @endcode
   *   In any case, keys/values of the previous node body are re-added, unless
   *   specified.
   * @param array $settings
   *   @todo
   */
  function inlineUpdateNode($node, $text, array $settings = array()) {
    $settings += array(
      'language' => LANGUAGE_NOT_SPECIFIED,
    );
    if (is_array($text)) {
      $body = $text;
    }
    else {
      $body['value'] = $text;
    }
    $body += $node->body[$settings['language']][0];
    $node->body[$settings['language']][0] = $body;
    node_save($node);
  }

  /**
   * Updates a node through the UI.
   *
   * @param Node $node
   *   The node object to update.
   * @param mixed $text
   *   For most cases, a simple string to use as the node's body value.
   *   Alternatively, an array specifying the field $item; e.g.,
   *   @code
   *   array('summary' => 'Foo', 'value' => 'Bar')
   *   @endcode
   *   In any case, keys/values of the previous node body are re-added, unless
   *   specified.
   * @param array $edit
   *   (optional) Additional form values to submit.
   */
  function inlineUpdateNodeUI($node, $text, array $edit = array()) {
    if (is_array($text)) {
      foreach ($text as $key => $value) {
        $edit['body[' . LANGUAGE_NOT_SPECIFIED . '][0][' . $key] = $value;
      }
    }
    else {
      $edit['body[' . LANGUAGE_NOT_SPECIFIED . '][0][value]'] = $text;
    }
    $this->drupalPost('node/' . $node->nid . '/edit', $edit, t('Save'));
  }
}
