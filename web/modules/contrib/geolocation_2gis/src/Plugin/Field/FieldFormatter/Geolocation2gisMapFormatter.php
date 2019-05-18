<?php

namespace Drupal\geolocation_2gis\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'geolocation2gis_map' formatter.
 *
 * @FieldFormatter(
 *   id = "geolocation2gis_map",
 *   module = "geolocation_2gis",
 *   label = @Translation("Geolocation 2GIS Map"),
 *   field_types = {
 *     "geolocation2gis"
 *   }
 * )
 */
class Geolocation2gisMapFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [];
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $geo_items = [];
    foreach ($items as $item) {
      $geo_items[] = [
        'lat' => $item->lat,
        'lng' => $item->lng,
        'description' => $item->lat . ', ' . $item->lng
      ];
    }

    $build = [
      '#theme' => 'geolocation_2gis_map_formatter',
      '#locations' => []
    ];

    $build['#attached']['library'][] = 'geolocation_2gis/api-2gis';
    $build['#attached']['library'][] = 'geolocation_2gis/map-2gis';
    $build['#attached']['drupalSettings']['locations'] = $geo_items;

    return $build;
  }

}
