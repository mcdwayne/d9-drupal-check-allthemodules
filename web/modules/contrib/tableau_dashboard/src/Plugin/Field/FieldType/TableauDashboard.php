<?php

namespace Drupal\tableau_dashboard\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type of TableauDashboard.
 *
 * @FieldType(
 *   id = "tableau_dashboard_field",
 *   label = @Translation("Tableau dashboard field"),
 *   default_formatter = "tableau_dashboard_formatter",
 *   default_widget = "tableau_dashboard_widget"
 * )
 */
class TableauDashboard extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'description' => 'Tablea Dashboard value.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
          'default' => '',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Tableau View'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
