<?php

namespace Drupal\pathalias_extend\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a suffix listing.
 */
class SuffixListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['target_entity'] = $this->t('Target entity');
    $header['pattern'] = $this->t('Pattern');
    $header['create_alias'] = $this->t('Create alias?');
    $header['enabled'] = $this->t('Enabled?');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['target_entity'] = $entity->getTargetEntityTypeId() . ':' . $entity->getTargetBundleId();
    $row['pattern'] = $entity->getPattern();
    $row['create_alias'] = $entity->getCreateAlias() ? $this->t('Yes') : $this->t('No');
    $row['enabled'] = $entity->status() ? $this->t('Yes') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
