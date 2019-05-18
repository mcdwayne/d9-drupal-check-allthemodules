<?php

namespace Drupal\Tests\recipe\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the functionality of the Recipe Total Time third-party settings.
 *
 * @group recipe
 */
class RecipeTotalTimeConfigTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['recipe'];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->entityTypeManager = $this->container->get('entity_type.manager');

    // Create and log in the admin user with Recipe content permissions.
    $permissions = [
      'create recipe content',
      'edit any recipe content',
      'administer site configuration'
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the integer field third-party settings.
   */
  public function testIntegerFieldTotalTimeConfig() {
    $title = $this->randomMachineName(16);
    $preptime = 60;
    $cooktime = 135;
    $new_field_value = 20;

    $edit = [
      'title[0][value]' => $title,
      'recipe_prep_time[0][value]' => $preptime,
      'recipe_cook_time[0][value]' => $cooktime,
    ];

    // Post the values to the node form.
    $this->drupalPostForm('node/add/recipe', $edit, 'Save');
    $this->assertSession()->pageTextContains(new FormattableMarkup('Recipe @title has been created.', ['@title' => $title]));

    // Check for the total time.
    $this->assertSession()->pageTextContains('3 hours, 15 minutes');

    // Add another integer field, but don't configure it as a total time field.
    $field_name = strtolower($this->randomMachineName());
    $field_settings = [
      'min' => 0,
    ];
    $this->createIntegerField($field_name, 'node', 'recipe', [], $field_settings);

    // Add a value for the new field to the existing node.
    $edit = [
      $field_name . '[0][value]' => $new_field_value,
    ];
    $this->drupalPostForm('node/1/edit', $edit, 'Save');

    // Check for the new field's value.
    $this->drupalGet('node/1');
    $this->assertSession()->pageTextContains('20 minutes');
    $this->assertSession()->pageTextContains('3 hours, 15 minutes');

    // Configure the new field as a total time field.
    $this->updateFieldThirdPartySetting($field_name, 'node', 'recipe', 'total_time', 1);

    // Check for the updated total time value.
    $this->drupalGet('node/1');
    $this->assertSession()->pageTextContains('3 hours, 35 minutes');

    // De-configure the recipe_cook_time field as a total time field.
    $this->updateFieldThirdPartySetting('recipe_cook_time', 'node', 'recipe', 'total_time', 0);

    // Check that fields can be de-configured as total time fields.
    $this->drupalGet('node/1');
    $this->assertSession()->pageTextContains('1 hour, 20 minutes');
  }

  /**
   * Creates a new integer field.
   *
   * @param string $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle that this field will be added to.
   * @param array $storage_settings
   *   A list of field storage settings that will be added to the defaults.
   * @param array $field_settings
   *   A list of instance settings that will be added to the instance defaults.
   * @param array $widget_settings
   *   A list of widget settings that will be added to the widget defaults.
   * @param array $display_settings
   *   A list of display settings that will be added to the display defaults.
   */
  protected function createIntegerField($name, $entity_type, $bundle, array $storage_settings = [], array $field_settings = [], array $widget_settings = [], array $display_settings = []) {
    $field_storage = $this->entityTypeManager->getStorage('field_storage_config')->create([
      'entity_type' => $entity_type,
      'field_name' => $name,
      'type' => 'integer',
      'settings' => $storage_settings,
      'cardinality' => !empty($storage_settings['cardinality']) ? $storage_settings['cardinality'] : 1,
    ]);
    $field_storage->save();

    $this->attachIntegerField($name, $entity_type, $bundle, $field_settings, $widget_settings, $display_settings);
    return $field_storage;
  }

  /**
   * Attaches an integer field to an entity.
   *
   * @param string $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param string $entity_type
   *   The entity type this field will be added to.
   * @param string $bundle
   *   The bundle this field will be added to.
   * @param array $field_settings
   *   A list of field settings that will be added to the defaults.
   * @param array $widget_settings
   *   A list of widget settings that will be added to the widget defaults.
   * @param array $display_settings
   *   A list of display settings that will be added to the display defaults.
   */
  protected function attachIntegerField($name, $entity_type, $bundle, array $field_settings = [], array $widget_settings = [], array $display_settings = []) {
    $field = [
      'field_name' => $name,
      'label' => $name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'required' => !empty($field_settings['required']),
      'settings' => $field_settings,
    ];
    $this->entityTypeManager->getStorage('field_config')->create($field)->save();

    $form_display = $this->entityTypeManager->getStorage('entity_form_display')->load($entity_type . '.' . $bundle . '.default');
    $form_display->setComponent($name, [
      'type' => 'number',
      'settings' => $widget_settings,
    ])
      ->save();
    // Assign display settings.
    $view_display = $this->entityTypeManager->getStorage('entity_view_display')->load($entity_type . '.' . $bundle . '.default');
    $view_display->setComponent($name, [
      'label' => 'hidden',
      'type' => 'recipe_duration',
      'settings' => $display_settings,
    ])
      ->save();
  }

  /**
   * Updates an integer field's third-party setting.
   */
  protected function updateFieldThirdPartySetting($name, $entity_type, $bundle, $setting_name, $value) {
    $field = FieldConfig::loadByName($entity_type, $bundle, $name);
    $field->setThirdPartySetting('recipe', $setting_name, $value);
    $field->save();
  }

}
