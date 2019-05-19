<?php

namespace Drupal\stock_photo_search\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TraversableTypedDataInterface;

/**
 * Plugin implementation of the 'stock_photo_search' field type.
 *
 * @FieldType(
 *   id = "stock_photo_search",
 *   label = @Translation("Stock Photo"),
 *   description = @Translation("Stores a 3rd party stock photo image reference (e.g. an Image ID)."),
 *   default_widget = "stock_photo_search_textfield",
 *   default_formatter = "stock_photo_search_image_formatter"
 * )
 */
class StockPhotoField extends FieldItemBase {

  /**
   * The embed provider plugin manager.
   *
   * @var \Drupal\stock_photo_search\ProviderManagerInterface
   */
  protected $providerManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL, $provider_manager = NULL) {
    parent::__construct($definition, $name, $parent);
    $this->providerManager = $provider_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL) {
    $provider_manager = \Drupal::service('stock_photo_search.provider_manager');

    return new static($definition, $name, $parent, $provider_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Image ID'))
      ->setDescription(new TranslatableMarkup('Supply a 3rd party stock photo image reference.'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();

    return empty($value);
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $form['allowed_providers'] = [
      '#title' => $this->t('Allow Provider'),
      '#description' => $this->t('Restrict users from entering information from the following providers. If none are selected any stock photo provider can be used.'),
      '#type' => 'radios',
      '#default_value' => $this->getSetting('allowed_providers'),
      '#options' => $this->providerManager->getProvidersOptionList(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'allowed_providers' => [],
    ];
  }

}
