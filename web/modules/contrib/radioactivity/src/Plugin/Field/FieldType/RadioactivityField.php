<?php

namespace Drupal\radioactivity\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'radioactivity' field type.
 *
 * @FieldType(
 *   id = "radioactivity",
 *   label = @Translation("Radioactivity"),
 *   description = @Translation("Radioactivity energy level and energy emitter"),
 *   default_widget = "radioactivity_energy",
 *   default_formatter = "radioactivity_emitter"
 * )
 */
class RadioactivityField extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'profile' => 'decay',
      'halflife' => 60 * 60 * 12,
      'granularity' => 60 * 15,
      'cutoff' => 1,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['energy'] = DataDefinition::create('float')
      ->setLabel(new TranslatableMarkup('Energy level'));

    $properties['timestamp'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Energy timestamp'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'energy' => [
          'description' => 'Energy level',
          'type' => 'float',
          'default' => 0,
        ],
        'timestamp' => [
          'description' => 'Timestamp of last emit',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['energy'] = 1;
    $values['timestamp'] = \Drupal::time()->getRequestTime();
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];

    $elements['profile'] = [
      '#type' => 'radios',
      '#title' => $this->t('Energy profile'),
      '#default_value' => $this->getSetting('profile'),
      '#required' => TRUE,
      '#options' => [
        'count' => 'Count',
        'linear' => 'Linear',
        'decay' => 'Decay',
      ],
      '#description' => $this->t('Count: Energy increases by 1 with each view. Never decreases.<br/>
Linear: Energy increases by the emission amount. Decreases by 1 per second.<br/>
Decay: Energy increases by the emission amount. Decreases 50% per half-life time.'),
    ];

    $elements['granularity'] = [
      '#type' => 'number',
      '#title' => $this->t('Granularity'),
      '#min' => 1,
      '#default_value' => $this->getSetting('granularity'),
      '#description' => $this->t('The time in seconds that the energy levels are kept before applying the decay.'),
      '#states' => [
        'visible' => [
          'input[name="settings[profile]"]' => [
            ['value' => 'linear'],
            ['value' => 'decay'],
          ],
        ],
      ],
    ];

    $elements['halflife'] = [
      '#type' => 'number',
      '#title' => $this->t('Half-life time'),
      '#min' => 1,
      '#default_value' => $this->getSetting('halflife'),
      '#description' => $this->t('The time in seconds in which the energy level halves.'),
      '#states' => [
        'visible' => [
          'input[name="settings[profile]"]' => ['value' => 'decay'],
        ],
      ],
    ];

    $elements['cutoff'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cutoff'),
      '#pattern' => '[0-9]+(\.[0-9]+)?',
      '#default_value' => $this->getSetting('cutoff'),
      '#description' => $this->t('Energy levels under this value is set to zero. Example: 0.5, 2.'),
      '#states' => [
        'invisible' => [
          'input[name="settings[profile]"]' => ['value' => 'count'],
        ],
      ],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();
    if (empty($this->energy)) {
      $this->energy = 0;
    }
    $this->timestamp = \Drupal::time()->getRequestTime();
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('energy')->getValue();
    return $value === NULL;
  }

}
