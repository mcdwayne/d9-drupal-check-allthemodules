<?php

namespace Drupal\Tests\cck_select_other\Functional;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * CCK Select Other functional test base class.
 */
abstract class CckSelectOtherTestBase extends BrowserTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'field_ui',
    'options',
    'cck_select_other',
  ];

  /**
   * A content type to test with.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $contentType;

  protected $adminUser;

  protected $webUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->contentType = $this->createContentType();

    $this->adminUser = $this->createUser(['administer content types', 'administer site configuration']);
    $this->webUser = $this->createUser([
      'access content',
      'create ' . $this->contentType->id() . ' content',
      'delete any ' . $this->contentType->id() . ' content',
      'bypass node access',
    ]);
  }

  /**
   * Creates a select other field on the content type.
   *
   * @param string $type
   *   The field type plugin ID.
   * @param array $fieldInfo
   *   The field storage configuration.
   * @param array $instanceInfo
   *   The field configuration.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\field\Entity\FieldStorageConfig
   *   The field config instance.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createSelectOtherListField($type = 'list_string', array $fieldInfo = [], array $instanceInfo = []) {
    $random = $this->getRandomGenerator();

    // Create field storage instance.
    $storage_values = NestedArray::mergeDeep($fieldInfo, [
      'field_name' => strtolower($random->name(8, TRUE)),
      'entity_type' => 'node',
      'type' => $type,
    ]);

    $fieldStorage = FieldStorageConfig::create($storage_values);
    $fieldStorage->save();
    $this->assertNotNull($fieldStorage->id(), 'Successfully saved field storage configuration.');

    // Create field instance.
    $field_values = NestedArray::mergeDeep($instanceInfo, [
      'field_name' => $fieldStorage->getName(),
      'entity_type' => 'node',
      'bundle' => $this->contentType->id(),
      'label' => $random->string(15),
    ]);
    $field = FieldConfig::create($field_values);
    $field->save();
    $this->assertNotNull($field->id(), 'Successfully saved field configuration.');

    // Create form and display entities for select other field.
    $display_id = 'node.' . $this->contentType->id() . '.default';
    $formDisplay = EntityFormDisplay::load($display_id);
    $formDisplay->setComponent($fieldStorage->getName(), [
      'type' => 'cck_select_other',
    ]);
    $formDisplay->save();

    $viewDisplay = EntityViewDisplay::load($display_id);
    $viewDisplay->setComponent($fieldStorage->getName(), [
      'type' => 'cck_select_other',
    ]);
    $viewDisplay->save();

    return $fieldStorage;
  }

  /**
   * Gets a random option in a list of options.
   *
   * @param array $options
   *   An array of list options.
   *
   * @return array
   *   An indexed array of the key and value.
   */
  public function getRandomOption(array $options) {
    $option = array_rand($options);
    return [$option, $options[$option]];
  }

  /**
   * Create select list options.
   *
   * @param int $num
   *   The number of options to create.
   * @param string $type
   *   The field type.
   *
   * @return array
   *   An associative array of allowed values keyed by value and the label as
   *   the array item value.
   */
  public function createOptions($num = 5, $type = 'list_string') {
    $options = [];
    for ($i = 0; $i < $num; $i++) {
      if ($type === 'list_string') {
        $label = $this->getRandomGenerator()->word(10);
        $key = strtolower($label);
      }
      else {
        $label = $i;
        $key = $i;
      }
      $options[$key] = $label;
    }
    return $options;
  }

  /**
   * Convert select list options into allowed values string copied from Core.
   *
   * @param array $values
   *   An associative array of values.
   *
   * @return string
   *   An allowed values string.
   *
   * @see \Drupal\options\Plugin\Field\FieldType\FieldItemBase::allowedValuesString()
   */
  public function allowedValuesString(array $values) {
    $lines = [];
    foreach ($values as $key => $value) {
      $lines[] = "$key|$value";
    }
    return implode("\n", $lines);
  }

}
