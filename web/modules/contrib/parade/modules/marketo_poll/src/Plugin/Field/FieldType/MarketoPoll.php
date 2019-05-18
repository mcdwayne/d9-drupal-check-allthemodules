<?php

namespace Drupal\marketo_poll\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'marketo_poll' field type.
 *
 * @FieldType(
 *   id = "marketo_poll",
 *   label = @Translation("Marketo poll"),
 *   module = "marketo_poll",
 *   description = @Translation("Marketo poll integration."),
 *   default_widget = "marketo_poll",
 *   default_formatter = "marketo_poll"
 * )
 */
class MarketoPoll extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'subscription_url' => [
          'description' => 'Subscription base URL',
          'type' => 'varchar',
          'length' => 2048,
        ],
        'poll_class' => [
          'description' => 'The poll class.',
          'type' => 'varchar',
          'length' => 255,
        ],
        'poll_id' => [
          'description' => 'The poll ID.',
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
    $poll_class = $this->get('poll_class')->getValue();
    $poll_id = $this->get('poll_id')->getValue();
    return !isset($subscription_url) || $subscription_url === '' ||
      !isset($poll_class) || $poll_class === '' ||
      !isset($poll_id) || $poll_id === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['subscription_url'] = DataDefinition::create('string')
      ->setLabel(t('Subscription base URL'));
    $properties['poll_class'] = DataDefinition::create('string')
      ->setLabel(t('Poll class'));
    $properties['poll_id'] = DataDefinition::create('string')
      ->setLabel(t('Poll ID'));

    return $properties;
  }

}
