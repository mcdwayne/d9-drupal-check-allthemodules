<?php

namespace Drupal\field_tooltips_data\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_tooltips_data' field type.
 *
 * @FieldType(
 *   id = "field_tooltips_data",
 *   label = @Translation("Tooltips data"),
 *   description = @Translation("This field stores Tooltips data fields in the database."),
 *   default_widget = "field_tooltips_data_widget",
 *   default_formatter = "field_tooltips_data_formatter"
 * )
 */
class TooltipsDataItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'text',
          'size' => 'big',
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    if (!isset($value) || !strlen($value)) {
      return TRUE;
    }
    return FALSE;

  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Tooltips'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    drupal_set_message(t('Be careful, field <em>@label</em> can be used only inside "Field image tooltips" Paragraphs bundle. It won\'t work in other places.', array('@label' => $this->getFieldDefinition()->getLabel())), 'warning');
    return $form;
  }

}
