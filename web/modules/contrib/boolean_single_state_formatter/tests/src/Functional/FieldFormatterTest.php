<?php

namespace Drupal\Tests\boolean_single_state_formatter\Functional;

use Drupal\Component\Utility\Unicode;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the "single state" boolean formatter.
 *
 * @group field
 */
class FieldFormatterTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'node',
    'user',
    'boolean_single_state_formatter',
  ];

  /**
   * The name of the entity bundle that is created in the test.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The name of the Boolean field to use for testing.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The text used for custom format, when value is FALSE.
   *
   * @var string
   */
  protected $defaultFormatCustomFalse;

  /**
   * The text used for custom format, when value is TRUE.
   *
   * @var string
   */
  protected $defaultFormatCustomTrue;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $type_name = Unicode::strtolower($this->randomMachineName(8)) . '_test';
    $type = $this->drupalCreateContentType(['name' => $type_name, 'type' => $type_name]);
    $this->bundle = $type->id();

    $admin_user = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'administer node display',
      'bypass node access',
      'administer nodes',
    ]);
    $this->drupalLogin($admin_user);

    $this->fieldName = Unicode::strtolower($this->randomMachineName(8));

    $this->defaultFormatCustomFalse = $this->randomString();
    $this->defaultFormatCustomTrue = $this->randomString();

    $field_storage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'type' => 'boolean',
    ]);
    $field_storage->save();

    $instance = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $this->bundle,
      'label' => $this->randomMachineName(),
    ]);
    $instance->save();

    $this->setBooleanFieldDisplaySettings();
  }

  /**
   * Test the generated display for various settings of the BOolean Single State formatter.
   */
  public function testBooleanFieldFormatter() {
    $node = $this->drupalCreateNode(['type' => $this->bundle, $this->fieldName => [0]]);
    $this->drupalGet($node->toUrl());
    $this->assertSession()->responseNotContains($this->defaultFormatCustomFalse);
    $this->assertSession()->responseNotContains($this->defaultFormatCustomTrue);

    $node = $this->drupalCreateNode(['type' => $this->bundle, $this->fieldName => [1]]);
    $this->drupalGet($node->toUrl());
    $this->assertSession()->responseNotContains($this->defaultFormatCustomFalse);
    $this->assertSession()->responseContains($this->defaultFormatCustomTrue);

    // Inverse state.
    $this->setBooleanFieldDisplaySettings(['format_inverse_state' => 1]);

    $node = $this->drupalCreateNode(['type' => $this->bundle, $this->fieldName => [0]]);
    $this->drupalGet($node->toUrl());
    $this->assertSession()->responseContains($this->defaultFormatCustomFalse);
    $this->assertSession()->responseNotContains($this->defaultFormatCustomTrue);

    $node = $this->drupalCreateNode(['type' => $this->bundle, $this->fieldName => [1]]);
    $this->drupalGet($node->toUrl());
    $this->assertSession()->responseNotContains($this->defaultFormatCustomFalse);
    $this->assertSession()->responseNotContains($this->defaultFormatCustomTrue);
  }

  /**
   * Helper method for the formatter settings.
   */
  protected function setBooleanFieldDisplaySettings($settings = []) {
    entity_get_display('node', $this->bundle, 'default')
      ->setComponent($this->fieldName, [
        'type' => 'boolean_single_state_formatter',
        'settings' => $settings + [
          'format' => 'custom',
          'format_custom_false' => $this->defaultFormatCustomFalse,
          'format_custom_true' => $this->defaultFormatCustomTrue,
          'format_inverse_state' => 0,
          ],
      ])
      ->save();
  }

}
