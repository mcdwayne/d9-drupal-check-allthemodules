<?php

namespace Drupal\simple_iframe\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'simple_iframe_field_type' field type.
 *
 * @FieldType(
 *   id = "simple_iframe_field_type",
 *   label = @Translation("Simple iframe"),
 *   description = @Translation("Simple iframe"),
 *   default_widget = "simple_iframe_widget_type",
 *   default_formatter = "simple_iframe_formatter_type"
 * )
 */
class SimpleIframeFieldType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'width' => '100%',
      'height' => '',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['url'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('URL'));

    $properties['width'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Width'));

    $properties['height'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Height'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'url' => [
          'description' => 'The URL of the iframe.',
          'type' => 'text',
          'length' => 2048,
          'not null' => FALSE,
        ],
        'width' => [
          'description' => 'The iframe width.',
          'type' => 'text',
          'length' => 255,
          'not null' => FALSE,
        ],
        'height' => [
          'description' => 'The iframe height.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // Set of possible top-level domains.
    $tlds = ['com', 'net', 'org'];
    // Set random length for the domain name.
    $domain_length = mt_rand(7, 15);
    $random = new Random();

    $values['url'] = '//' . $random->word($domain_length) . '.' . $tlds[mt_rand(0, (count($tlds) - 1))];
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['width'] = [
      '#type' => 'textfield',
      '#title' => t('Width'),
      '#default_value' => $this->getSetting('width'),
      '#required' => TRUE,
      '#description' => t('Default width of iframe. Set a number or %'),
    ];

    $elements['height'] = [
      '#type' => 'textfield',
      '#title' => t('Height'),
      '#default_value' => $this->getSetting('height'),
      '#required' => TRUE,
      '#description' => t('Default height of iframe.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('url')->getValue();
    return $value === NULL || $value === '';
  }

}
