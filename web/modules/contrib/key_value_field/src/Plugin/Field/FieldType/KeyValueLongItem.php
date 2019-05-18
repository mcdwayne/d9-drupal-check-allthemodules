<?php

namespace Drupal\key_value_field\Plugin\Field\FieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\text\Plugin\Field\FieldType\TextLongItem;

/**
 * Plugin implementation of the 'key_value' field type.
 *
 * @FieldType(
 *   id = "key_value_long",
 *   label = @Translation("Key / Value (long)"),
 *   description = @Translation("This field stores key value pairs."),
 *   category = @Translation("Key / Value"),
 *   default_widget = "key_value_textarea",
 *   default_formatter = "key_value",
 *   column_groups = {
 *     "key" = {
 *       "label" = @Translation("Key"),
 *       "translatable" = TRUE,
 *     },
 *     "value" = {
 *       "label" = @Translation("Value"),
 *       "translatable" = TRUE,
 *     },
 *     "description" = {
 *       "label" = @Translation("Description"),
 *       "translatable" = TRUE,
 *     },
 *   },
 * )
 */
class KeyValueLongItem extends TextLongItem {

  // Add overrides from the common trait.
  use KeyValueFieldTypeTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'default_format' => '',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $options = filter_formats();
    array_walk($options, function (&$item) {
      $item = $item->label();
    });

    $element['default_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Default text format.'),
      '#options' => $options,
      '#access' => count($options) > 1,
      '#default_value' => filter_default_format(),
      '#description' => $this->t('This set the default text format for new field items as long as the user has access to the default format. Default field settings override the default text format.'),
      '#attributes' => ['class' => ['filter-list']],
    ];

    return $element;
  }

}
