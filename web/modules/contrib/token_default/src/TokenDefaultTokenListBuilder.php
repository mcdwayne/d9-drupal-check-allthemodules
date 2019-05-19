<?php

namespace Drupal\token_default;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Default token entities.
 */
class TokenDefaultTokenListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Default token');
    $header['pattern'] = $this->t('Token Pattern');
    $header['replacement'] = $this->t('Replacement');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['pattern'] = $entity->getPattern();
    $row['replacement'] = $entity->getReplacement();
    return $row + parent::buildRow($entity);
  }

}
