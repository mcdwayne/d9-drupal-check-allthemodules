<?php

namespace Drupal\mixitup_views;

/**
 * Class MixitupViewsDefaultOptions.
 *
 * @package Drupal\mixitup_views
 */
class MixitupViewsDefaultOptionsService {

  /**
   * Get default option for mixitup js.
   *
   * @return array
   *   Array of default mixitup params.
   */
  public function defaultOptions($convert = FALSE) {
    $options = [
      'selectors' => [
        'target' => '.mix',
        'filter' => '.filter',
        'sort' => '.sort',
      ],
      'load' => [
        'filter' => 'all',
        'sort' => 'default:asc',
      ],
      'animation' => [
        'enable' => TRUE,
        'effects' => 'fade scale',
        'duration' => 600,
        'easing' => 'ease',
        'perspectiveDistance' => '3000px',
        'perspectiveOrigin' => '50% 50%',
        'queue' => TRUE,
        'queueLimit' => 1,
      ],
      'restrict' => [
        'vocab' => FALSE,
        'vocab_ids' => [],
      ],
    ];

    if ($convert) {
      $options = $this->convertFromMixitupOptions($options);
    }
    return $options;
  }

  /**
   * Convert mixitup options array to needed.
   *
   * @param array $mixitup_options
   *   Options array in mixitup js style.
   *
   * @return array
   *   Converted array of mixitup params.
   */
  public function convertFromMixitupOptions(array $mixitup_options) {
    $converted_options = [];
    foreach ($mixitup_options as $cat => $options) {
      foreach ($options as $option => $default_value) {
        $converted_options[$cat . '_' . $option] = $default_value;
      }
    }

    return $converted_options;
  }

}
