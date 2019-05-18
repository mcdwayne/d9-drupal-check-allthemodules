<?php

namespace Drupal\odoo;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class Config
 *
 * @package Drupal\odoo
 */
class Config implements ConfigInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Config constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  public function get($key) {
    return $this->configFactory->get('odoo.settings')->get($key);
  }

}
