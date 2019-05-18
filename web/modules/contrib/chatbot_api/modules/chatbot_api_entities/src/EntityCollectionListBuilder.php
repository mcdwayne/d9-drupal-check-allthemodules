<?php

namespace Drupal\chatbot_api_entities;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a list builder for entity collections.
 */
class EntityCollectionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    return ['collection' => $entity->toLink($entity->label(), 'edit-form')] + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return ['collection' => $this->t('Collection')] + parent::buildHeader();
  }

}
