<?php

namespace Drupal\cards\Plugin\Field\FieldType;


use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entityreference_view_mode\Plugin\Field\FieldType\EntityReferenceViewModeFieldType;

/**
 * Plugin implementation of the 'field_content_view' field type.
 *
 * @FieldType(
 *   id = "card_field_type",
 *   label = @Translation("Cards"),
 *   module = "cards",
 *   description = @Translation("Field referencing a piece of content and an
 *   associated view mode."), default_widget = "card_field_widget",
 *   default_formatter = "card_field_formatter"
 * )
 *
 */
class CardFieldType extends EntityReferenceViewModeFieldType {

  use CardViewTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'target_type' => 'node',
      'settings' => [],
    ];
  }

  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {

    $element = parent::fieldSettingsForm($form, $form_state);

    // @todo Move the loop here so that we can still keep the trait.

    $card_element = $this->addCardFieldSettingsForm($form, $form_state);
    $element =  NestedArray::mergeDeep($element,$card_element);


    return $element;
  }


  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    $schema = parent::schema($field_definition);


    $cards_schema = CardFieldType::addCardschema($field_definition);
    $schema =  NestedArray::mergeDeep($schema,$cards_schema);


    return $schema;

  }

  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties = parent::propertyDefinitions($field_definition);

    $properties += CardFieldType::addCardpropertyDefinitions($field_definition);
    return $properties;

  }

}
