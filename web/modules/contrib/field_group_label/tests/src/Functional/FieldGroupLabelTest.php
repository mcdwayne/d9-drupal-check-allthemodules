<?php

namespace Drupal\Tests\field_group_label\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field_group\Functional\FieldGroupTestTrait;

/**
 * Tests for Field Group Label.
 *
 * @group field_group_label
 */
class FieldGroupLabelTest extends BrowserTestBase {

  use FieldGroupTestTrait;

  /**
   * Do not check all configuration saved.
   *
   * @var bool
   *
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   */
  protected $strictConfigSchema = FALSE;

  /**
   * The node type id.
   *
   * @var string
   */
  protected $type;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'field',
    'field_ui',
    'field_group',
    'field_group_label',
  ];

  /**
   * Label default.
   *
   * @var string
   */
  protected static $labelDefault = "Field Group Label (Default Label)";

  /**
   * Label replacement.
   *
   * @var string
   */
  protected static $labelReplacement = "Field Group Label (Replacement)";

  /**
   * Label XPATH.
   *
   * @var string
   */
  protected static $labelXpath = "//div[contains(@id, :id)]/h3";

  /**
   * Field XPATH.
   *
   * @var string
   */
  protected static $fieldXpath = "//div[contains(@id, :id)]//div";

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create test user.
    $admin_user = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'administer node display',
    ]);
    // Login.
    $this->drupalLogin($admin_user);

    // Create content type.
    $type_name = mb_strtolower($this->randomMachineName(8)) . '_test';
    $type = $this->drupalCreateContentType(['name' => $type_name, 'type' => $type_name]);
    $this->type = $type;
    $this->contentType = $type->id();
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $display = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load('node.' . $type_name . '.default');

    $fields = [
      'field_field_group_label' => [
        'type' => 'field_group_label_field_type',
        'formatter' => 'field_group_label_formatter',
      ],
      'field_title' => [
        'type' => 'text',
        'formatter' => 'text_default',
      ],
    ];

    // Create fields and set in display.
    foreach ($fields as $field_name => $field) {
      $field_storage = FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'node',
        'type' => $field['type'],
      ]);
      $field_storage->save();
      $instance = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => $type_name,
        'label' => $this->randomMachineName(),
      ]);
      $instance->save();

      $display_options = [
        'label' => 'hidden',
        'type' => $field['formatter'],
      ];
      $display->setComponent($field_name, $display_options);
    }

    $display->save();

    // Create field group with children.
    $data = [
      'label' => self::$labelDefault,
      'weight' => '1',
      'children' => [
        0 => 'field_field_group_label',
        1 => 'field_title',
      ],
      'format_type' => 'html_element',
      'format_settings' => [
        'label' => self::$labelDefault,
        'element' => 'div',
        'show_label' => 1,
        'label_element' => 'h3',
        'id' => 'field-group-label-group',
      ],
    ];

    $this->createGroup('node', $this->contentType, 'view', 'default', $data);
  }

  /**
   * Check replacement.
   */
  public function testCheckReplacement() {
    $node = $this->drupalCreateNode([
      'type' => $this->contentType,
      'field_field_group_label' => self::$labelReplacement,
      'field_title' => t('Check replacement'),
    ]);

    $this->drupalGet('node/' . $node->id());

    $check = $this->xpath(self::$labelXpath, [':id' => 'field-group-label-group']);
    self::assertEquals(self::$labelReplacement, $check[0]->getText());
  }

  /**
   * Check default label if field has no value.
   */
  public function testCheckDefaultLabel() {
    $node = $this->drupalCreateNode([
      'type' => $this->contentType,
      'field_field_group_label' => '',
      'field_title' => t('Check default label if field has no value'),
    ]);
    $this->drupalGet('node/' . $node->id());

    $check = $this->xpath(self::$labelXpath, [':id' => 'field-group-label-group']);
    self::assertEquals(self::$labelDefault, $check[0]->getText());
  }

  /**
   * Check field visibility.
   */
  public function testFieldNotVisibleOnItsOwn() {
    $node = $this->drupalCreateNode([
      'type' => $this->contentType,
      'field_field_group_label' => self::$labelReplacement,
      'field_title' => t('Check field visibility'),
    ]);
    $this->drupalGet('node/' . $node->id());

    $check = $this->xpath(self::$fieldXpath, [':id' => 'field-group-label-group']);
    self::assertNotEquals(self::$labelReplacement, $check[0]->getText());
  }

}
