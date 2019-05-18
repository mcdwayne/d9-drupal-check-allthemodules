<?php

namespace Drupal\search_api_view_modes\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests creating views with the wizard and viewing them on the listing page.
 */
class SearchAPIViewModesTestCase extends WebTestBase {

  /**
   * The machine name of the created test index.
   *
   * @var string
   */
  protected $index_id;

  /**
   * Overrides DrupalWebTestCase::assertText().
   *
   * Changes the default message to be just the text checked for.
   */
  protected function assertText($text, $message = '', $group = 'Other') {
    return parent::assertText($text, $message ? $message : $text, $group);
  }

  /**
   * Overrides DrupalWebTestCase::drupalGet().
   *
   * Additionally asserts that the HTTP request returned a 200 status code.
   */
  protected function drupalGet($path, array $options = array(), array $headers = array()) {
    $ret = parent::drupalGet($path, $options, $headers);
    $this->assertResponse(200, 'HTTP code 200 returned.');
    return $ret;
  }

  /**
   * Overrides DrupalWebTestCase::drupalPost().
   *
   * Additionally asserts that the HTTP request returned a 200 status code.
   */
  protected function drupalPost($path, $edit, $submit, array $options = array(), array $headers = array(), $form_html_id = NULL, $extra_post = NULL) {
    $ret = parent::drupalPost($path, $edit, $submit, $options, $headers, $form_html_id, $extra_post);
    $this->assertResponse(200, 'HTTP code 200 returned.');
    return $ret;
  }

  /**
   * Returns information about this test case.
   *
   * @return array
   *   An array with information about this test case.
   */
  public static function getInfo() {
    return array(
      'name' => 'Test Search API View Modes',
      'description' => 'Provides tests for Search API View Modes',
      'group' => 'Search API View Modes',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp('node', 'search_api', 'search_api_view_modes');

    // set up node type
    $types = array(
      array(
        'type' => 'page',
        'name' => st('Basic page'),
        'base' => 'node_content',
        'description' => st("Use <em>basic pages</em> for your static content, such as an 'About us' page."),
        'custom' => 1,
        'modified' => 1,
        'locked' => 0,
      ),
    );

    foreach ($types as $type) {
      $type = node_type_set_defaults($type);
      node_type_save($type);
      node_add_body_field($type);
    }

    $this->index_id = $id = 'test_index';
    // set up dummy index, like a user would

    $form = drupal_get_form('search_api_admin_add_index');
    $form_state = array();
    $form_state['values'] = array(
      'name' => $this->index_id,
      'machine_name' => $this->index_id,
      'enabled' => TRUE,
      'item_type' => 'node',
      'options' => array(
        'datasource' => array(
          'bundles' => array('page')
        )
      )
    );

    drupal_form_submit('search_api_admin_add_index', $form_state);
  }

  /**
   * Tests that multiple view mode options are present on the form, and saves config properly.
   */
  public function testViewModeOptionsFieldExists() {
    $this->drupalLogin($this->drupalCreateUser(array('administer search_api')));

    $this->drupalGet('admin/config/search/search_api/index/' . $this->index_id . '/workflow');

    $this->assertText('Multiple entity views');
    $this->assertFieldbyId('edit-callbacks-multiple-entity-views-status');

    $values = array(
      'callbacks[multiple_entity_views][status]' => TRUE,
      'callbacks[multiple_entity_views][settings][modes][]' => array('full', 'teaser'),
    );

    $this->drupalPost(NULL, $values, t('Save configuration'));

    $index = search_api_index_load($this->index_id, TRUE);

    $modes = $index->options['data_alter_callbacks']['multiple_entity_views']['settings']['modes'];

    $this->assertEqual($index->options['data_alter_callbacks']['multiple_entity_views']['status'], TRUE, 'Multiple entity views is enabled.');
    $this->assertEqual($modes['full'], 'full', 'Full view modes are being indexed.');
    $this->assertEqual($modes['teaser'], 'teaser', 'Teaser view modes are being indexed.');
  }
}