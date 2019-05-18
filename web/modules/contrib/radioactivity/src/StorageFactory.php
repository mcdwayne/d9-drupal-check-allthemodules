<?php

namespace Drupal\radioactivity;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;

/**
 * Storage factory service.
 */
class StorageFactory {

  /**
   * The radioactivity storage configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * StorageFactory constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state storage service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, StateInterface $state) {
    $this->config = $configFactory->get('radioactivity.storage');
    $this->state = $state;
  }

  /**
   * Getter for classes which implement IncidentStorageInterface.
   *
   * @param string $type
   *   The type of storage to get.
   *
   * @return \Drupal\Radioactivity\IncidentStorageInterface
   *   Instance of the requested storage.
   */
  public function get($type) {

    static $instances = [];
    if (isset($instances[$type])) {
      return $instances[$type];
    }

    switch ($type) {
      case 'rest_local':
        $instance = new RestIncidentStorage();
        break;

      case 'rest_remote':
        $instance = new RestIncidentStorage($this->config->get('endpoint'));
        break;

      case 'default':
      default:
        $instance = new DefaultIncidentStorage($this->state);
    }

    $instances[$type] = $instance;

    return $instance;
  }

  /**
   * Get the configured incident storage.
   *
   * @return \Drupal\Radioactivity\IncidentStorageInterface
   *   The configured storage instance.
   */
  public function getConfiguredStorage() {
    $type = $this->config->get('type') ?: 'default';
    return $this->get($type);
  }

}
