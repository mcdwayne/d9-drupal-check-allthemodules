<?php

namespace Drupal\markjs_search\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Define the MarkJS profile listing builder.
 */
class MarkjsProfileList extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'name' => $this->t('Name')
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    return [
      'name' => $entity->label()
    ] + parent::buildRow($entity);
  }
}
