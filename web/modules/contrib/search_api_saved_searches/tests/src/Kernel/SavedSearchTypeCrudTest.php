<?php

namespace Drupal\Tests\search_api_saved_searches\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests CRUD functionality for saved search types.
 *
 * @group search_api_saved_searches
 * @coversDefaultClass \Drupal\search_api_saved_searches\Entity\SavedSearchType
 */
class SavedSearchTypeCrudTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'options',
    'search_api_saved_searches',
    'user',
  ];

  /**
   * Tests creation of a new saved search type.
   *
   * @covers ::postSave
   * @covers ::createFormDisplay
   * @covers ::adaptFieldStorageDefinitions
   */
  public function testTypeCreation() {
    // Ascertain the correct initial state.
    // Saved search entity type has no bundles.
    $bundles = $this->container->get('entity_type.bundle.info')
      ->getBundleInfo('search_api_saved_search');
    $this->assertEquals([], $bundles);
    // There is no "create" entity form display for the (non-existent) bundle
    $form_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load('search_api_saved_search.default.create');
    $this->assertNull($form_display);
    // There is no field storage present for the "mail" field.
    $field_storage = \Drupal::keyValue('entity.storage_schema.sql')
      ->get('search_api_saved_search.field_schema_data.mail');
    $this->assertNull($field_storage);

    // Just use the default type delivered with the module.
    $this->installEntitySchema('search_api_saved_search');
    $this->installConfig('search_api_saved_searches');

    // Bundle was created correctly.
    $bundles = $this->container->get('entity_type.bundle.info')
      ->getBundleInfo('search_api_saved_search');
    $this->assertEquals(['default'], array_keys($bundles));

    // The "create" form display was created for the new bundle and looks good.
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load('search_api_saved_search.default.create');
    $this->assertNotNull($form_display);
    $components = $form_display->getComponents();
    $this->assertEquals(['label', 'mail', 'notify_interval'], array_keys($components));
    $this->assertEquals('string_textfield', $components['label']['type']);
    $this->assertEquals('email_default', $components['mail']['type']);
    $this->assertEquals('options_select', $components['notify_interval']['type']);

    // The field storage for the bundle-specific "mail" field was created.
    $field_storage = \Drupal::keyValue('entity.storage_schema.sql')
      ->get('search_api_saved_search.field_schema_data.mail');
    $this->assertNotNull($field_storage);
  }

}
