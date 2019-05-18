<?php

namespace Drupal\imagepin\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for widget plugins.
 */
abstract class WidgetBase extends PluginBase implements WidgetInterface {

  /**
   * {@inheritdoc}
   */
  public function viewPinContent($value) {
    return ['#markup' => ''];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(&$value, array $belonging, $key = NULL) {}

  /**
   * {@inheritdoc}
   */
  public function getPosition($value) {
    return !empty($value['position']) ? $value['position'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setPosition(&$value, array $position) {
    $value['position'] = $position;
  }

}
