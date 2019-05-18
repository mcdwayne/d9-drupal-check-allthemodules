<?php

namespace Drupal\marketo_form\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'marketo_form' field type.
 *
 * @FieldType(
 *   id = "marketo_form",
 *   label = @Translation("Marketo form"),
 *   module = "marketo_form",
 *   description = @Translation("Marketo form integration."),
 *   default_widget = "marketo_form",
 *   default_formatter = "marketo_form"
 * )
 */
class MarketoForm extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'subscription_url' => [
          'description' => t('Subscription base URL'),
          'type' => 'varchar',
          'length' => 2048,
        ],
        'munchkin_id' => [
          'description' => t('The munchkin ID.'),
          'type' => 'varchar',
          'length' => 255,
        ],
        'form_id' => [
          'description' => t('The form ID.'),
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $subscription_url = $this->get('subscription_url')->getValue();
    $munchkin_id = $this->get('munchkin_id')->getValue();
    $form_id = $this->get('form_id')->getValue();
    return !isset($subscription_url) || $subscription_url === '' ||
      !isset($munchkin_id) || $munchkin_id === '' ||
      !isset($form_id) || $form_id === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['subscription_url'] = DataDefinition::create('string')
      ->setLabel(t('Subscription base URL'));
    $properties['munchkin_id'] = DataDefinition::create('string')
      ->setLabel(t('Munchkin ID'));
    $properties['form_id'] = DataDefinition::create('string')
      ->setLabel(t('Form ID'));

    return $properties;
  }

}
