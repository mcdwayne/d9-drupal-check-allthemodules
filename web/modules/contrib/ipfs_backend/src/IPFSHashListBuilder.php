<?php

namespace Drupal\ipfs_backend;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of IPFSHash entities.
 *
 * @ingroup ipfs_backend
 */
class IPFSHashListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('IPFSHash ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\ipfs_backend\Entity\IPFSHash */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.ipfs_hash.edit_form',
      ['ipfs_hash' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
