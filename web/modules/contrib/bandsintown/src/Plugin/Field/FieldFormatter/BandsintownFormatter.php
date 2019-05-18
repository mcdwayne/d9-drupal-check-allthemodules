<?php

namespace Drupal\bandsintown\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'bandsintown' formatter.
 *
 * @FieldFormatter(
 *   id = "bandsintown",
 *   module = "bandsintown",
 *   label = @Translation("Bandsintown"),
 *   field_types = {
 *     "bandsintown"
 *   }
 * )
 */
class BandsintownFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $service = \Drupal::service('bandsintown.helper');
    $elements = array();
    $bandsintown_settings = $service->bandsintownSettings();
    $module_config = \Drupal::config('bandsintown.settings');

    foreach ($items as $delta => $item) {

      $settings = array();
      foreach ($bandsintown_settings as $key => $setting) {
        $settings[$key] = $item->{$key};
      }

      $version = $module_config->get('widget_version') ? '_v2' : '';
      $elements[$delta] = array(
        '#type'     => 'item',
        '#title'    => $module_config->get('include_artist_name') ? $settings['data_artist'] : '',
        '#theme'    => 'bandsintown',
        '#attached' => array(
          'library' => array(
            'bandsintown/bit_widget' . $version,
          ),
        ),
        '#settings' => $settings,
      );
    }

    return $elements;
  }

}
