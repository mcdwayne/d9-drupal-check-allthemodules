<?php

namespace Drupal\custom_tokens\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * The token list builder.
 */
class TokenListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Token Name');
    $header['token'] = $this->t('Token');
    $header['value'] = $this->t('Value');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['token'] = sprintf('[%s]', $entity->getTokenName());
    $row['value'] = $entity->getTokenValue();
    return $row + parent::buildRow($entity);
  }

}
