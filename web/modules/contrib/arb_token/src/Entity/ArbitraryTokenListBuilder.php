<?php

namespace Drupal\arb_token\Entity;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class ArbitraryTokenListBuilder.
 */
class ArbitraryTokenListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $row['name'] = $this->t('Name');
    $row['type'] = $this->t('Type');
    $row['operations'] = $this->t('Operations');
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\arb_token\Entity\ArbitraryToken $entity */
    $row['name']['data'] = $entity->label();
    $row['type']['data'] = $entity->getType();
    $row['operations']['data'] = $this->buildOperations($entity);
    return $row;
  }

}
