<?php

namespace Drupal\Tests\widget_engine\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\widget_engine\Entity\Widget;
use Drupal\widget_engine\Entity\WidgetType;

/**
 * Tests \Drupal\Paragraphs\Entity\Paragraph::isPublished().
 *
 * @group widget_engine
 */
class WidgetIsPublishedTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'widget_engine',
    'image',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $widget_type = WidgetType::create([
      'label' => 'Test Widget',
      'id' => 'test_widget',
    ]);
    $widget_type->save();
    $this->addWidgetField('test_widget', 'text', 'string');
    $this->installEntitySchema('widget');
  }

  /**
   * Tests the functionality of the isPublished() function.
   */
  public function testIsPublished() {
    $widget = Widget::create([
      'type' => 'test_widget',
      'text' => 'some text',
    ]);
    $widget->save();
    $this->assertTrue($widget->isPublished(), 'Widget is publised');
    $widget->setPublished(FALSE);
    $this->assertFalse($widget->isPublished(), 'Widget is unpublised');
  }

  /**
   * Adds a field to a given widget type.
   *
   * @param string $widget_type_name
   *   Paragraph type name to be used.
   * @param string $field_name
   *   Paragraphs field name to be used.
   * @param string $field_type
   *   Type of the field.
   * @param array $field_edit
   *   Edit settings for the field.
   */
  protected function addWidgetField($widget_type_name, $field_name, $field_type, array $field_edit = []) {
    // Add a paragraphs field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'widget',
      'type' => $field_type,
      'cardinality' => '-1',
      'settings' => $field_edit,
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $widget_type_name,
      'settings' => [
        'handler' => 'default',
        'handler_settings' => ['target_bundles' => NULL],
      ],
    ]);
    $field->save();
  }

}
