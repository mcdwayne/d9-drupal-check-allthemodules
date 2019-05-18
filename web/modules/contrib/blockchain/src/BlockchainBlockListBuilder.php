<?php

namespace Drupal\blockchain;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Blockchain Block entities.
 *
 * @ingroup blockchain
 */
class BlockchainBlockListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = 20;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {

    $header['id'] = $this->t('Id');
    $header['author'] = $this->t('Author');
    $header['timestamp'] = $this->t('Timestamp');
    $header['nonce'] = $this->t('Nonce');
    $header['previous_hash'] = $this->t('Previous hash');

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {

    /* @var $entity \Drupal\blockchain\Entity\BlockchainBlock */
    $row['id'] = $entity->toLink($entity->id());
    $row['author'] = $entity->getAuthor();
    $row['timestamp'] = $entity->getTimestamp();
    $row['nonce'] = $entity->getNonce();
    $row['previous_hash'] = $entity->getPreviousHash();

    return $row;
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return '!!!';
  }

}
