<?php

namespace Drupal\just_giving\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'just_giving_field_type' field type.
 *
 * @FieldType(
 *   id = "just_giving_field_type",
 *   label = @Translation("Just Giving fields"),
 *   description = @Translation("Provides values for Just Giving API"),
 *   default_widget = "just_giving_widget_type",
 *   default_formatter = "just_giving_formatter_type"
 * )
 */
class JustGivingFieldType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'max_length' => 2000,
      'short_length' => 200,
      'is_ascii' => FALSE,
      'case_sensitive' => FALSE,
      'jg_page_type' => '',
    ] + parent::defaultStorageSettings();;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.

    $properties['cause_id'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Campaign ID'))
      ->setDescription(new TranslatableMarkup('The ID number of the linked campaign'))
      ->setRequired(FALSE);

    $properties['event_id'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Event ID'))
      ->setDescription(new TranslatableMarkup('The ID number of the campaign event'))
      ->setRequired(FALSE);

    $properties['page_story'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Page Story'))
      ->setDescription(new TranslatableMarkup('Description of the campaign or event'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(FALSE);

    $properties['page_summary_what'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Page Summary What'))
      ->setDescription(new TranslatableMarkup('Appears in What Summary'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(FALSE);

    $properties['page_summary_why'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Page Summary Why'))
      ->setDescription(new TranslatableMarkup('Appears In Why Summary'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(FALSE);

    $properties['suggested_target_amount'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Suggested Target Amount'))
      ->setDescription(new TranslatableMarkup('Will appear on sign up form'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(FALSE);

    $properties['charity_id'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Charity ID'))
      ->setDescription(new TranslatableMarkup('Over ride default charity id'))
      ->setRequired(FALSE);

    $properties['page_type'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Page Type'))
      ->setDescription(new TranslatableMarkup('Choice of fundraising type'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'cause_id' => [
          'description' => 'Campaign Id',
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
        'event_id' => [
          'description' => 'Event Id',
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
        'page_story' => [
          'description' => 'Page Story',
          'type' => $field_definition->getSetting('is_ascii') === TRUE ? 'varchar_ascii' : 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
          'binary' => $field_definition->getSetting('case_sensitive'),
        ],
        'page_summary_what' => [
          'description' => 'Page What',
          'type' => $field_definition->getSetting('is_ascii') === TRUE ? 'varchar_ascii' : 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
          'binary' => $field_definition->getSetting('case_sensitive'),
        ],
        'page_summary_why' => [
          'description' => 'Page Why',
          'type' => $field_definition->getSetting('is_ascii') === TRUE ? 'varchar_ascii' : 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
          'binary' => $field_definition->getSetting('case_sensitive'),
        ],
        'suggested_target_amount' => [
          'description' => 'Suggested Target',
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
        'charity_id' => [
          'description' => 'Charity Id',
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
        'page_type' => [
          'description' => 'Campaign or Event type.',
          'type' => $field_definition->getSetting('is_ascii') === TRUE ? 'varchar_ascii' : 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
          'binary' => $field_definition->getSetting('case_sensitive'),
        ],
      ],
    ];

    return $schema;
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
      '#description' => t('The maximum length of the field in characters.'),
      '#min' => 1,
      '#disabled' => $has_data,
    ];

    $elements['short_length'] = [
      '#type' => 'number',
      '#title' => t('Short length'),
      '#default_value' => $this->getSetting('short_length'),
      '#description' => t('The maximum length of the field in characters.'),
      '#min' => 1,
      '#disabled' => $has_data,
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::fieldSettingsForm($form, $form_state);

    $form['jg_page_type'] = [
      '#type' => 'select',
      '#title' => t('Choose Page Type'),
      '#description' => $this->t('Choose if fundraising pages should be for a campaign or an event'),
      '#options' => [
        'choose' => $this->t('Please Choose Type'),
        'event' => $this->t('Event'),
        'campaign' => $this->t('Campaign'),
      ],
      '#default_value' => $this->getSetting('jg_page_type'),
    ];

    return $form;
  }

  /**
   * @return bool
   */
  public function isEmpty() {

    $isEmpty =
      empty($this->get('cause_id')->getValue()) &&
      empty($this->get('event_id')->getValue()) &&
      empty($this->get('page_story')->getValue()) &&
      empty($this->get('page_summary_what')->getValue()) &&
      empty($this->get('page_summary_why')->getValue()) &&
      empty($this->get('suggested_target_amount')->getValue()) &&
      empty($this->get('charity_id')->getValue()) &&
      empty($this->get('page_type')->getValue());

    return $isEmpty;
  }
}
