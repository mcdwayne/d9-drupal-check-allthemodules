<?php

namespace Drupal\chinese_identity_card\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * @FieldType(
 *   id = "chinese_identity_card",
 *   label = @Translation("Chinese identity card"),
 *   description = @Translation("Store Chinese identity card."),
 *   default_widget = "chinese_identity_card_default",
 *   default_formatter = "chinese_identity_card"
 * )
 */
class ChineseIdentityCardItem extends FieldItemBase {

  /**
   * @inheritdoc
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Chinese identity card'));

    return $properties;
  }

  /**
   * @inheritdoc
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'description' => 'ID Number',
          'type' => 'char',
          'length' => 18,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
        'validator_id' => 'default_chinese_identity_card_validator',
      ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    // Get base form from FileItem.
    $element = parent::fieldSettingsForm($form, $form_state);

    $settings = $this->getSettings();

    /** @var \Drupal\chinese_identity_card\Plugin\ChineseIdentityCardValidatorManager $chinese_identity_card_validator */
    $chinese_identity_card_validator = \Drupal::service('plugin.manager.chinese_identity_card_validator');
    $definitions = $chinese_identity_card_validator->getDefinitions();

    $options = [];
    foreach ($definitions as $definition) {
      $options[$definition['id']] = $definition['description'];
    }

    $element['validator_id'] = [
      '#type' => 'select',
      '#title' => t('Validator'),
      '#description' => t('Set a validator.'),
      '#options' => $options,
      '#default_value' => $settings['validator_id'],
      '#required' => TRUE,
    ];

    return $element;
  }


  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $bits_18 = [
      '130204199109038931',
      '652722199110058570',
      '371425199303219099',
      '440883199310284676',
      '511321198101269814',
      '140622198704184572',
      '33072319880109797X',
      '469002199112252315',
      '41132119860220613X',
      '231202198612085396',
    ];

    $bits_18_length = count($bits_18);
    $rand_index = mt_rand(0, $bits_18_length);
    $values['value'] = $bits_18[$rand_index];

    return $values;
  }
}