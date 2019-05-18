<?php

namespace Drupal\geolocation\Plugin\geolocation\MapCenter;

use Drupal\geolocation\MapCenterInterface;
use Drupal\geolocation\MapCenterBase;

/**
 * Fixed coordinates map center.
 *
 * ID for compatibility with v1.
 *
 * @MapCenter(
 *   id = "fit_bounds",
 *   name = @Translation("Fit locations"),
 *   description = @Translation("Automatically fit map to displayed locations."),
 * )
 */
class FitLocations extends MapCenterBase implements MapCenterInterface {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings() {
    return [
      'reset_zoom' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm($option_id = NULL, array $settings = [], $context = NULL) {
    $form = parent::getSettingsForm($option_id, $settings, $context);
    $form['reset_zoom'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reset zoom after fit.'),
      '#default_value' => $settings['reset_zoom'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $map, $center_option_id, array $center_option_settings, $context = NULL) {
    $map = parent::alterMap($map, $center_option_id, $center_option_settings, $context);
    $map['#attached'] = array_merge_recursive($map['#attached'], [
      'library' => [
        'geolocation/map_center.fitlocations',
      ],
    ]);

    return $map;
  }

}
