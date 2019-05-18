<?php

namespace Drupal\transactionalphp;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class TransactionalPhpFactory.
 *
 * @package Drupal\transactionalphp
 *
 * @author Thomas Gielfeldt <thomas@gielfeldt.dk>
 */
class TransactionalPhpFactory {

  use ContainerAwareTrait;

  /**
   * Singleton array of instances.
   *
   * @var \Drupal\transactionalphp\TransactionalPhp[][]
   */
  protected $transactionalPHPs = [];

  /**
   * Create transactional php instance for a specific connection.
   *
   * @param mixed $connection
   *   The database connection.
   *
   * @return \Drupal\transactionalphp\TransactionalPhp
   *   The transactional php instance.
   */
  public function get($connection) {
    $key = $connection->getKey();
    $target = $connection->getTarget();
    if (!isset($this->transactionalPHPs[$key][$target])) {
      $this->transactionalPHPs[$key][$target] = new TransactionalPhp($connection);
      $this->transactionalPHPs[$key][$target]->setContainer($this->container);
    }
    return $this->transactionalPHPs[$key][$target];
  }

}
