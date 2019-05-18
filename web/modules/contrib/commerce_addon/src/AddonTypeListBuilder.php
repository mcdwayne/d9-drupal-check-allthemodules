<?php

namespace Drupal\commerce_addon;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines the list builder for addon types.
 */
class AddonTypeListBuilder extends EntityListBuilder {

  /**
   * @inheritdoc
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * Builds the row.
   *
   * @inheritdoc
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_addon\Entity\AddonTypeInterface $entity */
    $row['id'] = $entity->id();
    $row['name'] = $entity->label();

    return $row + parent::buildRow($entity);
  }

}
