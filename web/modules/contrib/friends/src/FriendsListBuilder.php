<?php

namespace Drupal\friends;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;

/**
 * Defines a class to build a listing of Friends entities.
 *
 * @ingroup friends
 */
class FriendsListBuilder extends EntityListBuilder {
  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['requester'] = $this->t('Requester');
    $header['recipient'] = $this->t('Recipient');
    $header['type'] = $this->t('Type');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $this->l(
      $entity->id(),
      $entity->toUrl()
    );

    $row['requester'] = $this->l(
      $entity->getOwner()->label(),
      $entity->getOwner()->toUrl()
    );

    $row['recipient'] = $this->l(
      $entity->getRecipient()->label(),
      $entity->getRecipient()->toUrl()
    );

    $row['type'] = $entity->getType();
    $row['status'] = $entity->getStatus();

    return $row + parent::buildRow($entity);
  }

}
