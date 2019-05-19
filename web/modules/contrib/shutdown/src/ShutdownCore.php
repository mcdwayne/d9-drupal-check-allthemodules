<?php

namespace Drupal\shutdown;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class ShutdownCore.
 *
 * @package Drupal\shutdown
 */
class ShutdownCore {

  /**
   * Shutdown settings config object.
   *
   * @var Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs the shutdown core service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config) {
    $this->config = $config->getEditable('shutdown.settings');
  }

  /**
   * Performs needed actions to shut down the website.
   */
  public function shutWebsite() {
    $this->config
      ->set('shutdown_enable', 1)
      ->save();

    \Drupal::logger('shutdown')->notice('Website has been shut down.');
  }

  /**
   * Performs needed actions to shut down the website.
   */
  public function openWebsite() {
    $this->config
      ->set('shutdown_enable', 0)
      ->save();

    \Drupal::logger('shutdown')->notice('Website has been opened up.');
  }

}
