<?php

namespace Drupal\node_subscription;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Node subscription entities.
 *
 * @ingroup node_subscription
 */
class NodeSubscriptionStorageListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Node subscription ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\node_subscription\Entity\NodeSubscriptionStorage */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.node_subscription_storage.edit_form',
      ['node_subscription_storage' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
