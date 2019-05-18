<?php

namespace Drupal\Tests\datetime_extras\Kernel;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\datetime_extras\Plugin\Field\FieldWidget\DateTimeDatelistNoTimeWidget;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test the DateTimeConfigurableList for datetime fields.
 *
 * @coversDefaultClass \Drupal\datetime_extras\Plugin\Field\FieldWidget\DateTimeDatelistNoTimeWidget
 * @group datetime_extras
 */
class DateTimeDatelistNoTimeWidgetTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'datetime',
    'datetime_extras',
  ];

  /**
   * @covers ::__construct
   */
  public function testConstruction() {
    $base_field_definition = BaseFieldDefinition::create('datetime')
      ->setName('Configurable List');

    $widget_options = [
      'field_definition' => $base_field_definition,
      'form_mode' => 'default',
      'configuration' => [
        'type' => 'datetime_datelist_no_time',
      ],
    ];

    $instance = $this->container->get('plugin.manager.field.widget')->getInstance($widget_options);
    $this->assertInstanceOf(DateTimeDatelistNoTimeWidget::class, $instance);
  }

}
