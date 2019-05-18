<?php

namespace Drupal\odoo;

use Jsg\Odoo\Odoo;

/**
 * Class Client
 *
 * @package Drupal\odoo
 */
class Client {

  protected $config;

  /**
   * Client constructor.
   *
   * @param \Drupal\odoo\ConfigInterface $config
   *   The configuration for the API connection.
   */
  public function __construct(ConfigInterface $config) {
    $this->config = $config;
  }

  /**
   * Returns an OdooClient to communicate with an Odoo endpoint.
   *
   * @return \Jsg\Odoo\Odoo
   */
  public function client() {
    return new Odoo(
      $this->config->get('endpoint'),
      $this->config->get('database'),
      $this->config->get('user'),
      $this->config->get('pass')
    );
  }

}
