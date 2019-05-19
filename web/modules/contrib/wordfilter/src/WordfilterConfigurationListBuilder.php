<?php

namespace Drupal\wordfilter;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Wordfilter configuration entities.
 */
class WordfilterConfigurationListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Wordfilter configuration');
    $header['id'] = $this->t('Machine name');
    $header['process'] = $this->t('Filtering process');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['process'] = $entity->get('process_id');
    return $row + parent::buildRow($entity);
  }

}
