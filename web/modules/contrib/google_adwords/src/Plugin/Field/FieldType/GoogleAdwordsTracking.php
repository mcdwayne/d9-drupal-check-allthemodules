<?php

/**
 * @file
 * Contains \Drupal\google_adwords\Plugin\Field\FieldType\GoogleAdwordsTracking.
 */

namespace Drupal\google_adwords\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'google_adwords_tracking' field type.
 *
 * @FieldType(
 *   id = "google_adwords_tracking",
 *   label = @Translation("Google AdWords tracking"),
 *   category = @Translation("Statistics"),
 *   description = @Translation("Google AdWords added to entity"),
 *   default_widget = "google_adwords_text",
 *   default_formatter = "google_adwords_inlinejs"
 * )
 */
class GoogleAdwordsTracking extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array(
      'max_length' => 255,
      'is_ascii' => FALSE,
      'case_sensitive' => FALSE,
    ) + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslationWrapper.

    $properties['words'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Google AdWords'))
      ->setSetting('case_sensitive', FALSE)
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'words';
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = array(
      'columns' => array(
        'words' => array(
          'type' => $field_definition->getSetting('is_ascii') === TRUE ? 'varchar_ascii' : 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
          'binary' => $field_definition->getSetting('case_sensitive'),
        ),
      ),
    );

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    if ($max_length = $this->getSetting('max_length')) {
      $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
      $constraints[] = $constraint_manager->create('ComplexData', array(
        'words' => array(
          'Length' => array(
            'max' => $max_length,
            'maxMessage' => t('%name: may not be longer than @max characters.', array(
              '%name' => $this->getFieldDefinition()->getLabel(),
              '@max' => $max_length
            )),
          ),
        ),
      ));
    }

    return $constraints;
  }


  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    /**
     * @var \Drupal\Core\Config\ImmutableConfig $config
     *   saved settings for google_adwords
     */
    $config = \Drupal::config('google_adwords.settings');

    return [
      'conversion_id' => $config->get('conversion_label'),
      'label' => $config->get('label'),
      'langcode' => $config->get('langcode'),
      'colour' => $config->get('colour'),
      'format' => $config->get('format')
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {

    $element = [
      '#type' => 'fieldset',
      '#title' => new TranslatableMarkup('Google Adwords conversion settings'),
      '#description' => new TranslatableMarkup('Configure how Google AdWord conversions will be sent for this field.  Settings used here will override the global AdWords settings when this field is used.'),
    ];

    $element['conversion_id'] = array(
      '#type'=>'textfield',
      '#title'=> new TranslatableMarkup('Google Adwords conversion id'),
      '#default_value'=> $this->getSetting('conversion_id')
    );
    $element['language'] = array(
      '#type'=>'textfield',
      '#title'=> new TranslatableMarkup('Google Adwords conversion language override'),
      '#default_value'=> $this->getSetting('language')
    );
    $element['format'] = array(
      '#type'=>'textfield',
      '#title'=> new TranslatableMarkup('Google Adwords conversion format'),
      '#default_value'=> $this->getSetting('format')
    );
    $element['colour'] = array(
      '#type'=>'textfield',
      '#title'=> new TranslatableMarkup('Google Adwords conversion colour'),
      '#default_value'=> $this->getSetting('color')
    );
    $element['label'] = array(
      '#type'=>'textfield',
      '#title'=> new TranslatableMarkup('Google Adwords conversion label'),
      '#default_value'=> $this->getSetting('label')
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['words'] = $random->word(mt_rand(1, $field_definition->getSetting('max_length')));
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('words')->getValue();
    return $value === NULL || $value === '';
  }

}
