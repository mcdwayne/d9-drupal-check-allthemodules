<?php

/**
 * @file
 * Contains Drupal\impression\Plugin\QueueWorker\HouseKeeperBase.php
 */

namespace Drupal\impression\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides base functionality for the Impression Queue Workers.
 */
abstract class HouseKeeperBase extends QueueWorkerBase {
  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $ids = $data->nid;
    entity_delete_multiple('impression_base', $ids);
  }

}
