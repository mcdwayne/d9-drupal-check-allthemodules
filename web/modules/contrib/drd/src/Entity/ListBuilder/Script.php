<?php

namespace Drupal\drd\Entity\ListBuilder;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Script entities.
 */
class Script extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Script');
    $header['id'] = $this->t('Machine name');
    $header['type'] = $this->t('Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\drd\Entity\ScriptInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['type'] = $entity->type();
    return $row + parent::buildRow($entity);
  }

}
