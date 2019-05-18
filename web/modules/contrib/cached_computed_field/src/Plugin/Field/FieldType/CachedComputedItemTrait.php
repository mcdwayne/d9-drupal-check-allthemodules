<?php

namespace Drupal\cached_computed_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Reusable code for cached computed field types.
 */
trait CachedComputedItemTrait {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['expire'] = DataDefinition::create('integer')
      ->setLabel(t('Expiration timestamp'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'cache-max-age' => 3600,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['expire'] = [
      'type' => 'int',
      'unsigned' => TRUE,
      'size' => 'normal',
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::fieldSettingsForm($form, $form_state);

    $settings = $this->getSettings();

    $element['cache-max-age'] = [
      '#type' => 'select',
      '#title' => t('Cache lifetime'),
      '#options' => [
        60 => t('1 minute'),
        300 => t('5 minutes'),
        900 => t('15 minutes'),
        1800 => t('30 minutes'),
        3600 => t('1 hour'),
        7200 => t('2 hours'),
        21600 => t('6 hours'),
        43200 => t('12 hours'),
        86400 => t('1 day'),
        604800 => t('1 week'),
      ],
      '#default_value' => $settings['cache-max-age'],
      '#description' => t('The amount of time the data in the field should remain cached.'),
    ];

    return $element;
  }

}
