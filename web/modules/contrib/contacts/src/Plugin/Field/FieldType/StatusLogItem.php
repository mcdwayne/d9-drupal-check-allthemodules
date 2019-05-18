<?php

namespace Drupal\contacts\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'status_log' field type.
 *
 * @FieldType(
 *   id = "status_log",
 *   label = @Translation("Status log"),
 *   description = @Translation("Logs changes to a status"),
 *   default_formatter = "status_log_list",
 *   list_class = "\Drupal\contacts\StatusLogItemList"
 * )
 */
class StatusLogItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'max_length' => 255,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Status'))
      ->setRequired(TRUE);
    $properties['previous'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Previous Status'));
    $properties['timestamp'] = DataDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Changed timestamp'))
      ->setRequired(TRUE);
    $properties['uid'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('User responsible for change'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
        ],
        'previous' => [
          'type' => 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
        ],
        'timestamp' => [
          'type' => 'int',
        ],
        'uid' => [
          'type' => 'int',
        ],
      ],
      'indexes' => [
        'value' => ['value'],
        'previous' => ['previous'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();
    if ($max_length = $this->getSetting('max_length')) {
      $constraint_manager = \Drupal::typedDataManager()
        ->getValidationConstraintManager();
      $constraints[] = $constraint_manager->create('ComplexData', [
        'value' => [
          'Length' => [
            'max' => $max_length,
            'maxMessage' => t('%name: may not be longer than @max characters.', [
              '%name' => $this->getFieldDefinition()->getLabel(),
              '@max' => $max_length,
            ]),
          ],
        ],
      ]);
    }
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['value'] = $random->word(mt_rand(1, $field_definition->getSetting('max_length')));
    $values['previous'] = $random->word(mt_rand(1, $field_definition->getSetting('max_length')));
    $values['uid'] = mt_rand(0, 1);
    $values['timestamp'] = mt_rand(1262055681, 1262055681);
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];
    $elements['max_length'] = [
      '#type' => 'number',
      '#title' => t('Maximum length'),
      '#default_value' => $this->getSetting('max_length'),
      '#required' => TRUE,
      '#description' => $this->t('The maximum length of the field in characters.'),
      '#min' => 1,
      '#disabled' => $has_data,
    ];

    // @todo Make this field a select field of existing fields on entity.
    $elements['source_field'] = [
      '#type' => 'textfield',
      '#title' => t('Source Field'),
      '#default_value' => $this->getSetting('source_field'),
      '#required' => TRUE,
      '#description' => $this->t('The field that stores the status being logged.'),
      '#disabled' => $has_data,
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
