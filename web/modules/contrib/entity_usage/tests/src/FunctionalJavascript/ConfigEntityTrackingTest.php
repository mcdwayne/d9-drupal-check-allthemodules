<?php

namespace Drupal\Tests\entity_usage\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\entity_usage\Traits\EntityUsageLastEntityQueryTrait;
use Drupal\user\Entity\Role;

/**
 * Tests tracking of config entities.
 *
 * @package Drupal\Tests\entity_usage\FunctionalJavascript
 *
 * @group entity_usage
 */
class ConfigEntityTrackingTest extends EntityUsageJavascriptTestBase {

  use EntityUsageLastEntityQueryTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'views',
    'webform',
    'block',
    'block_field',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load('authenticated');
    $this->grantPermissions($role, [
      'administer entity usage',
      'access entity usage statistics',
      'administer webform',
    ]);

  }

  /**
   * Tests webform tracking.
   */
  public function testWebformTracking() {

    // Create an entity reference field pointing to a webform.
    $storage = FieldStorageConfig::create([
      'field_name' => 'field_eu_test_related_webforms',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'webform',
      ],
    ]);
    $storage->save();
    FieldConfig::create([
      'bundle' => 'eu_test_ct',
      'entity_type' => 'node',
      'field_name' => 'field_eu_test_related_webforms',
      'label' => 'Related Webforms',
      'settings' => [
        'handler' => 'default:webform',
        'handler_settings' => [
          'target_bundles' => NULL,
          'auto_create' => FALSE,
        ],
      ],
    ])->save();

    // Define our widget and formatter for this field.
    entity_get_form_display('node', 'eu_test_ct', 'default')
      ->setComponent('field_eu_test_related_webforms', [
        'type' => 'entity_reference_autocomplete',
      ])
      ->save();
    entity_get_display('node', 'eu_test_ct', 'default')
      ->setComponent('field_eu_test_related_webforms', [
        'type' => 'entity_reference_label',
      ])
      ->save();

    $this->drupalPlaceBlock('local_tasks_block');
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Check some config-entity related settings on the config form.
    $this->drupalGet('/admin/config/entity-usage/settings');

    // We should have an unchecked checkbox for a local tab.
    $webform_tab_checkbox = $assert_session->fieldExists('local_task_enabled_entity_types[entity_types][webform]');
    $assert_session->checkboxNotChecked('local_task_enabled_entity_types[entity_types][webform]');

    // Check it so we can test it later.
    $webform_tab_checkbox->click();

    // We should have an unchecked checkbox for source/target entity type.
    $sources_fieldset_wrapper = $assert_session->elementExists('css', '#edit-track-enabled-source-entity-types summary');
    $sources_fieldset_wrapper->click();
    $assert_session->fieldExists('track_enabled_source_entity_types[entity_types][webform]');
    $assert_session->checkboxNotChecked('track_enabled_source_entity_types[entity_types][webform]');
    $targets_fieldset_wrapper = $assert_session->elementExists('css', '#edit-track-enabled-target-entity-types summary');
    $targets_fieldset_wrapper->click();
    $webform_target_checkbox = $assert_session->fieldExists('track_enabled_target_entity_types[entity_types][webform]');
    $assert_session->checkboxNotChecked('track_enabled_target_entity_types[entity_types][webform]');

    // Check tracking webforms as targets.
    $webform_target_checkbox->click();

    // Save configuration.
    $page->pressButton('Save configuration');
    $this->saveHtmlOutput();

    // Make sure the 'contact' webform exists.
    $this->drupalGet('/form/contact');
    $page->findField('email');
    $page->findButton('Send message');

    // Create a node referencing this webform.
    $this->drupalGet('/node/add/eu_test_ct');
    $page->fillField('title[0][value]', 'Node that points to a webform');
    $page->fillField('field_eu_test_related_webforms[0][target_id]', 'Contact (contact)');
    $page->pressButton('Save');
    $this->saveHtmlOutput();
    $this->assertSession()->pageTextContains('eu_test_ct Node that points to a webform has been created.');

    // Visit the webform page, check the usage tab is there.
    $webform_link = $assert_session->elementExists('css', '.field--name-field-eu-test-related-webforms a');
    $webform_link->click();
    $this->saveHtmlOutput();

    // Click on the tab and verify if the usage was correctly tracked.
    $assert_session->pageTextContains('Usage');
    $page->clickLink('Usage');
    $this->saveHtmlOutput();
    // We should be at /webform/contact/usage.
    $this->assertContains("/webform/contact/usage", $session->getCurrentUrl());
    $assert_session->elementContains('css', 'main table', 'Node that points to a webform');
    $assert_session->elementContains('css', 'main table', 'Related Webforms');
  }

  /**
   * Tests block_field / views tracking.
   */
  public function testBlockFieldTracking() {

    // Create block field on the node type.
    $storage = FieldStorageConfig::create([
      'field_name' => 'field_eu_test_related_views',
      'entity_type' => 'node',
      'type' => 'block_field',
    ]);
    $storage->save();
    FieldConfig::create([
      'bundle' => 'eu_test_ct',
      'entity_type' => 'node',
      'field_name' => 'field_eu_test_related_views',
      'label' => 'Related Views',
    ])->save();

    // Define our widget and formatter for this field.
    entity_get_form_display('node', 'eu_test_ct', 'default')
      ->setComponent('field_eu_test_related_views', [
        'type' => 'block_field_default',
      ])
      ->save();
    entity_get_display('node', 'eu_test_ct', 'default')
      ->setComponent('field_eu_test_related_views', [
        'type' => 'block_field',
      ])
      ->save();

    $this->drupalPlaceBlock('local_tasks_block');
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Check some config-entity related settings on the config form.
    $this->drupalGet('/admin/config/entity-usage/settings');

    // We should have an unchecked checkbox for source/target entity type.
    $sources_fieldset_wrapper = $assert_session->elementExists('css', '#edit-track-enabled-source-entity-types summary');
    $sources_fieldset_wrapper->click();
    $assert_session->fieldExists('track_enabled_source_entity_types[entity_types][view]');
    $assert_session->checkboxNotChecked('track_enabled_source_entity_types[entity_types][view]');
    $targets_fieldset_wrapper = $assert_session->elementExists('css', '#edit-track-enabled-target-entity-types summary');
    $targets_fieldset_wrapper->click();
    $view_target_checkbox = $assert_session->fieldExists('track_enabled_target_entity_types[entity_types][view]');
    $assert_session->checkboxNotChecked('track_enabled_target_entity_types[entity_types][view]');

    // Check tracking views as targets.
    $view_target_checkbox->click();

    // Save configuration.
    $page->pressButton('Save configuration');
    $this->saveHtmlOutput();

    // Make sure our target view exists.
    $view_name = 'content_recent';
    $view = \Drupal::entityTypeManager()->getStorage('view')->load($view_name);
    $this->assertNotNull($view);

    // Create a node referencing this view through a Block Field field.
    $this->drupalGet('/node/add/eu_test_ct');
    $page->fillField('title[0][value]', 'Node that points to a block with a view');
    $assert_session->optionExists('field_eu_test_related_views[0][plugin_id]', "views_block:{$view_name}-block_1");
    $page->selectFieldOption('field_eu_test_related_views[0][plugin_id]', "views_block:{$view_name}-block_1");
    $assert_session->assertWaitOnAjaxRequest();
    $this->saveHtmlOutput();
    $page->pressButton('Save');
    $this->saveHtmlOutput();
    $this->assertSession()->pageTextContains('eu_test_ct Node that points to a block with a view has been created.');
    /** @var \Drupal\node\NodeInterface $host_node */
    $host_node = $this->getLastEntityOfType('node', TRUE);

    // Check that usage for this view is correctly tracked.
    $usage = \Drupal::service('entity_usage.usage')->listSources($view);
    $expected = [
      'node' => [
        $host_node->id() => [
          [
            'source_langcode' => $host_node->language()->getId(),
            'source_vid' => $host_node->getRevisionId(),
            'method' => 'block_field',
            'field_name' => 'field_eu_test_related_views',
            'count' => 1,
          ],
        ],
      ],
    ];
    $this->assertEquals($expected, $usage);
  }

}
