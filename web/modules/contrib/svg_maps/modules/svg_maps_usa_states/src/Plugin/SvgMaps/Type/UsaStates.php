<?php

namespace Drupal\svg_maps_usa_states\Plugin\SvgMaps\Type;

use Drupal\svg_maps\SvgMapsTypeBase;

/**
 * Provides svg maps type plugin for usa states.
 *
 * @SvgMapsType(
 *   id = "usa_states",
 *   label = @Translation("USA states"),
 *   description = @Translation("USA states")
 * )
 */
class UsaStates extends SvgMapsTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getGlobalTheme(){
    return 'svg_maps_usa_states';
  }

  /**
   * {@inheritdoc}
   */
  public function getDetailedTheme(){
    return 'svg_maps_usa_states_detail';
  }

  protected function buildItemConfigurationForm($currentPath = NULL) {
    $item = parent::buildItemConfigurationForm($currentPath);

    $code = [
      '#type' => 'textfield',
      '#title' => $this->t('State\'s code'),
      '#description' => $this->t("State's code."),
      '#weight' => -2,
    ];

    if($currentPath) {
      $code['#default_value'] = $currentPath['code'];
    }
    $item['code'] = $code;

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
