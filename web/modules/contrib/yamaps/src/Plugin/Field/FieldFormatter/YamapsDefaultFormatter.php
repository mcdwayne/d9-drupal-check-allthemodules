<?php

namespace Drupal\yamaps\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'yamaps' formatter.
 *
 * @FieldFormatter(
 *   id = "yamaps_default",
 *   label = @Translation("Yandex Map Field default"),
 *   field_types = {
 *     "yamaps"
 *   }
 * )
 */
class YamapsDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $map_behaviors = [];

    if ($this->getSetting('enable_zoom')) {
      $map_behaviors[] = 'scrollZoom';
      $map_behaviors[] = 'dblClickZoom';
    }

    if ($this->getSetting('enable_drag')) {
      $map_behaviors[] = 'drag';
    }

    foreach ($items as $delta => $item) {

      $map_id = $items->getName() . '-' . $delta;
      // Map information.
      $default_js = yamaps_format_values_to_js($item);

      // Map initialization parameters.
      $map = [
        'init' => [
          'center' => $default_js['coords']['center'] ?? NULL,
          'zoom' => $default_js['coords']['zoom'] ?? NULL,
          'type' => 'yandex#map',
          'behaviors' => $map_behaviors,
        ],
        'display_options' => [
          'display_type' => 'map',
          'width' => $this->getSetting('width'),
          'height' => $this->getSetting('height'),
        ],
        'controls' => 1,
        'placemarks' => $this->getFieldSetting('enable_placemarks') ? $default_js['placemarks'] : [],
        'lines' => $this->getFieldSetting('enable_lines') ? $default_js['lines'] : [],
        'polygons' => $this->getFieldSetting('enable_polygons') ? $default_js['polygons'] : [],
        'edit' => FALSE,
      ];
      $element = [
        '#theme' => 'yamaps_field_formatter',
        '#width' => $this->getSetting('width'),
        '#height' => $this->getSetting('height'),
        '#map_id' => $map_id,
      ];
      $elements['#attached']['drupalSettings']['yamaps'][$map_id] = $map;
      $elements[$delta] = $element;
    }

    $elements['#attached']['library'][] = 'yamaps/yandex-map-api';
    if ($this->getFieldSetting('enable_placemarks')) {
      $elements['#attached']['library'][] = 'yamaps/yamaps-placemark';
    }
    if ($this->getFieldSetting('enable_lines')) {
      $elements['#attached']['library'][] = 'yamaps/yamaps-line';
    }

    if ($this->getFieldSetting('enable_polygons')) {
      $elements['#attached']['library'][] = 'yamaps/yamaps-polygon';
    }
    $elements['#attached']['library'][] = 'yamaps/yamaps-map';

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['width'] = [
      '#title' => $this->t('Map width'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('width'),
    ];

    $element['height'] = [
      '#title' => $this->t('Map height'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('height'),
    ];

    $element['enable_drag'] = [
      '#title' => $this->t('Allow the user to drag a map.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('enable_drag'),
    ];

    $element['enable_zoom'] = [
      '#title' => $this->t('Allow the user to zoom a map.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('enable_zoom'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'width' => '100%',
      'height' => '400px',
      'enable_drag' => true,
      'enable_zoom' => true,
    ] + parent::defaultSettings();
  }

}
