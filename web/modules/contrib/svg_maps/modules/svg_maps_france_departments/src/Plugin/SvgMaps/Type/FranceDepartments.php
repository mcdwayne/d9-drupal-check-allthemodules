<?php

namespace Drupal\svg_maps_france_departments\Plugin\SvgMaps\Type;

use Drupal\svg_maps\SvgMapsTypeBase;

/**
 * Provides svg maps type plugin for fr departments.
 *
 * @SvgMapsType(
 *   id = "france_departments",
 *   label = @Translation("France departments"),
 *   description = @Translation("France departments")
 * )
 */
class FranceDepartments extends SvgMapsTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getGlobalTheme(){
    return 'svg_maps_france_departments';
  }

  /**
   * {@inheritdoc}
   */
  public function getDetailedTheme(){
    return 'svg_maps_france_departments_detail';
  }

  protected function buildItemConfigurationForm($currentPath = NULL) {
    $item = parent::buildItemConfigurationForm($currentPath);

    $class = [
      '#type' => 'textfield',
      '#title' => $this->t('Class attribute'),
      '#description' => $this->t("Svg class."),
      '#weight' => -1,
    ];

    if($currentPath) {
      $class['#default_value'] = $currentPath['class'];
    }
    $item['class'] = $class;

    return $item;
  }

}
