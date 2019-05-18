<?php

namespace Drupal\drd\Entity\ListBuilder;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Script Type entities.
 */
class ScriptType extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Script');
    $header['id'] = $this->t('Machine name');
    $header['interpreter'] = $this->t('Interpreter');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\drd\Entity\ScriptTypeInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['interpreter'] = $entity->interpreter();
    return $row + parent::buildRow($entity);
  }

}
