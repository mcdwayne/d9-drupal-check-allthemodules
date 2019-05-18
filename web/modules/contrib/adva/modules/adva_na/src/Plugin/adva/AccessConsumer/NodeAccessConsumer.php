<?php

namespace Drupal\adva_na\Plugin\adva\AccessConsumer;

use Drupal\adva\Entity\AccessConsumerInterface as AccessConsumerEntityInterface;
use Drupal\adva\Plugin\adva\AccessConsumer;

/**
 * Defines an access consumer for nodes.
 *
 * @AccessConsumer(
 *  id = "node",
 *  entityType = "node",
 * )
 */
class NodeAccessConsumer extends AccessConsumer {

  /**
   * {@inheritdoc}
   */
  public function onChange(AccessConsumerEntityInterface $config) {
    parent::onChange($config);

    node_access_needs_rebuild(TRUE);
  }

}
