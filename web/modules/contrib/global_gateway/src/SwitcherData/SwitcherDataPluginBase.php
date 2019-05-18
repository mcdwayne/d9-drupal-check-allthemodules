<?php

namespace Drupal\global_gateway\SwitcherData;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Defines a base switcher data item implementation.
 */
abstract class SwitcherDataPluginBase extends PluginBase implements SwitcherDataPluginInterface, PluginInspectionInterface, ContainerFactoryPluginInterface {

  protected $label;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('id');
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->get('label');
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    if (!empty($this->configuration[$key])) {
      return $this->configuration[$key];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->configuration[$key] = $value;
  }

}
