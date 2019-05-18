<?php

namespace Drupal\blockchain;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of Blockchain Node entities.
 */
class BlockchainNodeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = 20;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {

    $header['id'] = $this->t('Id');
    $header['label'] = $this->t('Label');
    $header['blockchain_type'] = $this->t('Blockchain type');
    $header['self_id'] = $this->t('Self id');
    $header['endpoint'] = $this->t('Endpoint');
    $header['ip'] = $this->t('Real ip');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {

    /** @var \Drupal\blockchain\Entity\BlockchainNodeInterface $entity */
    $row['id'] = $entity->id();
    $row['label'] = $entity->label();
    $row['blockchain_type'] = $entity->getBlockchainTypeId();
    $row['self_id'] = $entity->getSelf();
    $row['endpoint'] = $entity->getEndPoint();
    $row['ip'] = $entity->getIp()? $entity->getIp() : $this->t('none');

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {

    /** @var \Drupal\blockchain\Entity\BlockchainNodeInterface $entity */
    $operations = parent::getDefaultOperations($entity);
    $operations['sync'] = [
      'title' => $this->t('Sync'),
      'weight' => 10,
      'url' => Url::fromRoute('blockchain.api.pull',[
        'blockchain_config' => $entity->getBlockchainTypeId(),
        'blockchain_node' => $entity->id(),
      ]),
    ];

    return $operations;
  }

}
