<?php

namespace Drupal\Tests\ingredient\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\simpletest\ContentTypeCreationTrait;

/**
 * Provides common helper methods for Ingredient field tests.
 */
trait IngredientTestTrait {

  use ContentTypeCreationTrait {
    createContentType as drupalCreateContentType;
  }

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Gets the entity type manager service.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager service.
   */
  protected function getEntityTypeManager() {
    if (!$this->entityTypeManager) {
      $this->entityTypeManager = $this->container->get('entity_type.manager');
    }

    return $this->entityTypeManager;
  }

  /**
   * Sets up a node bundle for Ingredient field testing.
   */
  protected function ingredientCreateContentType() {
    $this->drupalCreateContentType(['type' => 'test_bundle']);
  }

  /**
   * Creates a new ingredient field.
   *
   * @param array $storage_settings
   *   A list of field storage settings that will be added to the defaults.
   * @param array $field_settings
   *   A list of instance settings that will be added to the instance defaults.
   * @param array $widget_settings
   *   A list of widget settings that will be added to the widget defaults.
   * @param array $display_settings
   *   A list of display settings that will be added to the display defaults.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface
   *   The ingredient field's storage definition.
   */
  protected function createIngredientField(array $storage_settings = [], array $field_settings = [], array $widget_settings = [], array $display_settings = []) {
    $field_storage = $this->getEntityTypeManager()->getStorage('field_storage_config')->create([
      'entity_type' => 'node',
      'field_name' => 'field_ingredient',
      'type' => 'ingredient',
      'settings' => $storage_settings,
      'cardinality' => !empty($storage_settings['cardinality']) ? $storage_settings['cardinality'] : 1,
    ]);
    $field_storage->save();

    $this->attachIngredientField($field_settings, $widget_settings, $display_settings);
    return $field_storage;
  }

  /**
   * Attaches an ingredient field to an entity.
   *
   * @param array $field_settings
   *   A list of field settings that will be added to the defaults.
   * @param array $widget_settings
   *   A list of widget settings that will be added to the widget defaults.
   * @param array $display_settings
   *   A list of display settings that will be added to the display defaults.
   */
  protected function attachIngredientField(array $field_settings = [], array $widget_settings = [], array $display_settings = []) {
    $field = [
      'field_name' => 'field_ingredient',
      'label' => $this->randomMachineName(16),
      'entity_type' => 'node',
      'bundle' => 'test_bundle',
      'required' => !empty($field_settings['required']),
      'settings' => $field_settings,
    ];
    $this->getEntityTypeManager()->getStorage('field_config')->create($field)->save();

    // @todo Replace these two calls to \Drupal::entityTypeManager() with
    //   getEntityTypeManager() and the two calls in updateIngredientField().
    //   For some reason, replacing them causes a strange database exception
    //   about not being able to create a cache table or something, even though
    //   the underlying code - the calls to the service manager - is EXACTLY THE
    //   SAME.  There have been very few reports of people having similar
    //   issues and none of them have been about a situation that is exactly the
    //   same as this.
    $form_display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('node.test_bundle.default');
    $form_display->setComponent('field_ingredient', [
      'type' => 'ingredient_autocomplete',
      'settings' => $widget_settings,
    ])
      ->save();
    // Assign display settings.
    $view_display = \Drupal::entityTypeManager()->getStorage('entity_view_display')->load('node.test_bundle.default');
    $view_display->setComponent('field_ingredient', [
      'label' => 'hidden',
      'type' => 'ingredient_default',
      'settings' => $display_settings,
    ])
      ->save();
  }

  /**
   * Updates an existing ingredient field with new settings.
   *
   * @param array $field_settings
   *   A list of field settings that will be added to the defaults.
   * @param array $widget_settings
   *   A list of widget settings that will be added to the widget defaults.
   * @param array $display_settings
   *   A list of display settings that will be added to the display defaults.
   */
  protected function updateIngredientField(array $field_settings = [], array $widget_settings = [], array $display_settings = []) {
    $field = FieldConfig::loadByName('node', 'test_bundle', 'field_ingredient');
    $field->setSettings(array_merge($field->getSettings(), $field_settings));
    $field->save();

    $form_display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('node.test_bundle.default');
    $form_display->setComponent('field_ingredient', ['settings' => $widget_settings])
      ->save();

    $view_display = \Drupal::entityTypeManager()->getStorage('entity_view_display')->load('node.test_bundle.default');
    $view_display->setComponent('field_ingredient', ['settings' => $display_settings])
      ->save();
  }

}
