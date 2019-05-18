<?php

namespace Drupal\read_time;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Database\Connection;

/**
 * Defines a book manager.
 */
class ReadTimeManager {

  /**
   * Entity manager Service Object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Config Factory Service Object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a BookManager object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $connection, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function updateReadTime(NodeInterface $node, $read_time = NULL) {

    if (empty($node->id())) {
      return FALSE;
    }
    $this->connection->merge('read_time')
      ->key(['nid' => $node->id()])
      ->fields([
        'read_time' => $read_time,
      ])
      ->execute();

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteReadTime($nid) {
    return $this->connection->delete('read_time')
      ->condition('nid', $nid)
      ->execute();
  }

}
