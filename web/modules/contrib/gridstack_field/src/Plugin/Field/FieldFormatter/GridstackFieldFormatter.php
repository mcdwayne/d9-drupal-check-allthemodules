<?php

namespace Drupal\gridstack_field\Plugin\Field\FieldFormatter;


use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\gridstack_field\GridstackFieldHelper;

/**
 * Plugin implementation of the 'postal_code' formatter.
 *
 * @FieldFormatter(
 *   id = "gridstack_field_formatter",
 *   label = @Translation("Gridstack field"),
 *   field_types = {
 *     "gridstack_field"
 *   }
 * )
 */
class GridstackFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [
      'height' => '0',
      'width' => '12',
      'cellHeight' => '60',
      'minWidth' => '768',
      'rtl' => 'auto',
      'verticalMargin' => '10',
      'animate' => 0,
      'alwaysShowResizeHandle' => 0,
      'auto' => 1,
      'disableDrag' => 0,
      'disableResize' => 0,
      'float' => 0,
    ] + parent::defaultSettings();

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $settings = $this->getFieldSettings();

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#type' => 'markup',
        '#markup' => $item->json,
      ];

      // Converting options to boolean type for preventing issues
      // with incorrect types.
      $options = GridstackFieldHelper::getOptions('bool');
      foreach ($options as $option) {
        $settings[$option] = (bool) $settings[$option];
      }

      // Converting options to int type for preventing issues
      // with incorrect types.
      $options = GridstackFieldHelper::getOptions('int');
      foreach ($options as $option) {
        $settings[$option] = intval($settings[$option]);
      }

      // Pass settings into script.
      $element['#attached']['drupalSettings']['gridstack_field']['settings'] = $settings;

      // Add Backbone, Underscore and Gridstack libraries.
      $element['#attached']['library'][] = 'gridstack_field/gridstack_field.library';
    }

    return $element;
  }
}
