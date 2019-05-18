<?php

namespace Drupal\cmlexchange\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * CommerceML DebugService service.
 */
class DebugService implements DebugServiceInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new DebugService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Debug.
   */
  public function debug($function, $message) {
    $debug = FALSE;
    $config = $this->configFactory->get('cmlexchange.settings');
    if ($config->get('debug')) {
      \Drupal::logger($function)->notice($message);
      $debug = TRUE;
    }
    return $debug;
  }

}
