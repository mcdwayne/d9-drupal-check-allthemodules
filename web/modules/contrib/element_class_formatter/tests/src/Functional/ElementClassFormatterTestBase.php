<?php

namespace Drupal\Tests\element_class_formatter\Functional;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Defines a base class for testing element_class_formatter functionality.
 */
abstract class ElementClassFormatterTestBase extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use UserCreationTrait;
  use NodeCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'element_class_formatter',
    'entity_test',
    'field',
    'file',
    'image',
    'link',
    'node',
    'user',
    'responsive_image',
    'system',
    'telephone',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected $runAgainstInstalledSite = TRUE;

  /**
   * Creates a field and set's the correct formatter.
   *
   * @param string $formatter
   *   The formatter ID.
   * @param string $field_type
   *   The type of field to create.
   * @param array $formatter_settings
   *   Settings for the formatter.
   *
   * @return \Drupal\field\Entity\FieldConfig
   *   Newly created file field.
   */
  protected function createEntityField($formatter, $field_type, array $formatter_settings = []) {
    $entity_type = $bundle = 'entity_test';
    $field_name = Unicode::strtolower($this->randomMachineName());

    FieldStorageConfig::create([
      'entity_type' => $entity_type,
      'field_name' => $field_name,
      'type' => $field_type,
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    $field_config = FieldConfig::create([
      'entity_type' => $entity_type,
      'field_name' => $field_name,
      'bundle' => $bundle,
    ]);
    $field_config->save();

    $values = [
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'full',
      'status' => TRUE,
    ];
    $display = EntityViewDisplay::create($values);

    $display->setComponent($field_name, [
      'type' => $formatter,
      'settings' => $formatter_settings,
    ])->save();

    return $field_config;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->createUser(['view test entity']));
  }

}
