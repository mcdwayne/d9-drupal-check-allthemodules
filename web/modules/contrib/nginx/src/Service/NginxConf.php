<?php

namespace Drupal\nginx\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class NginxConf.
 */
class NginxConf implements NginxConfInterface {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Creates a new NginxConf Service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    // Services.
    $config = $config_factory->get('ngxin.settings');
    $this->configFactory = $config_factory;
    $this->config = $config;
  }

  /**
   * Config get.
   */
  public function get() {
    $renderable = [
      '#theme' => 'ngxin-conf',
      '#data' => [
        'vhosts' => '/etc/nginx/vhosts',
        'include' => 'include /etc/nginx/default/default.conf;',
      ],
    ];
    $config = \Drupal::service('renderer')->renderRoot($renderable);
    return $config;
  }

}
