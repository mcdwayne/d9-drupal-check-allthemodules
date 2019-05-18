<?php

/**
 * @file
 * Contains \Drupal\adv_varnish\UserBlockBase.
 */

namespace Drupal\adv_varnish;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\Entity;

class UserBlockBase extends PluginBase implements UserBlockInterface {

  /**
   * @var string
   */
  protected $content;

  /**
   * @inheritdoc
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration = $configuration;
  }

  /**
   * Generate cache config key for given entity.
   *
   * @return string
   */
  public static function content() {
    return '';
  }
  
}
