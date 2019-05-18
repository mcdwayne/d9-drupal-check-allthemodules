<?php

namespace Drupal\headline_group\Plugin\Field\FieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\headline_group\HeadlineGroupItemInterface;

/**
 * Plugin implementation of the 'headline_group' field type.
 *
 * @FieldType(
 *   id = "headline_group",
 *   label = @Translation("Headline Group"),
 *   module = "field_monolith",
 *   description = @Translation("A headline with accompanying superhead and subhead elements."),
 *   default_widget = "headline_complete",
 *   default_formatter = "headline_default"
 * )
 */
class HeadlineGroupItem extends FieldItemBase implements HeadlineGroupItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'superhead' => [
          'type' => 'text',
          'size' => 'medium',
          'not null' => FALSE,
        ],
        'headline' => [
          'type' => 'text',
          'size' => 'medium',
          'not null' => FALSE,
        ],
        'subhead' => [
          'type' => 'text',
          'size' => 'medium',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['superhead'] = DataDefinition::create('string')
      ->setLabel(t('Superhead'));

    $properties['headline'] = DataDefinition::create('string')
      ->setLabel(t('Headline'));

    $properties['subhead'] = DataDefinition::create('string')
      ->setLabel(t('Subhead'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'include_superhead' => static::HG_SUPERHEAD,
      'include_subhead' => static::HG_SUBHEAD,
      'title_behavior' => static::HG_OVERRIDE,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $element['include_superhead'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow adding a superhead (text above the headline).'),
      '#default_value' => $this->getSetting('include_superhead'),
      '#return_value' => static::HG_SUPERHEAD,
    ];

    $element['title_behavior'] = [
      '#type' => 'radios',
      '#title' => t('Headline behavior'),
      '#default_value' => $this->getSetting('title_behavior'),
      '#options' => [
        static::HG_BLANK => t('Do not use the entity title'),
        static::HG_OVERRIDE => t('Fall back to entity title if headline left empty'),
        static::HG_PROHIBIT => t('Always use entity title (disable headline)'),
      ],
    ];

    $element['include_subhead'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow adding a subhead (text below the headline).'),
      '#default_value' => $this->getSetting('include_subhead'),
      '#return_value' => static::HG_SUBHEAD,
    ];

    return $element;
  }

}
