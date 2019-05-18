<?php

namespace Drupal\prev_next;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Driver\mysql\Connection;

/**
 * Defines an PrevNextHelper service.
 */
class PrevNextHelper implements PrevNextHelperInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs an PrevNextHelper object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Configuration Factory.
   */
  public function __construct(Connection $database, ConfigFactoryInterface $config_factory) {
    $this->database = $database;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleNames() {
    $bundle_names = [];
    foreach ($this->configFactory->listAll('prev_next.node_type.') as $config) {
      $contents = explode('.', $config);
      $bundle_names[] = end($contents);
    }
    return $bundle_names;
  }

  /**
   * {@inheritdoc}
   */
  public function loadBundle($bundle_name) {
    return $this->configFactory->get('prev_next.node_type.' . $bundle_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getPrevnextId($entity_id, $op = 'next') {
    switch ($op) {
      case 'prev':
        return $this->getPrevId($entity_id);

      case 'next':
        return $this->getNextId($entity_id);

      default:
        return 0;

    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPrevId($entity_id) {
    return $this->database->query("SELECT prev_nid FROM {prev_next_node} WHERE nid = :nid", array(':nid' => $entity_id))->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getNextId($entity_id) {
    return $this->database->query("SELECT next_nid FROM {prev_next_node} WHERE nid = :nid", array(':nid' => $entity_id))->fetchField();
  }

}
