<?php

namespace Drupal\dropshark\Collector;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Interface CollectorInterface.
 */
interface CollectorInterface extends ContainerAwareInterface, ContainerFactoryPluginInterface {

  /**
   * Indicates a successful collection of data.
   *
   * Non-zero values indicate that the collection failed.
   */
  const STATUS_SUCCESS = 0;

  /**
   * Collect data.
   *
   * @param array $data
   *   Optional, data utilized by the collector.
   */
  public function collect(array $data = []);

  /**
   * Perform checks which were deferred until the end of the request.
   */
  public function finalize();

}
