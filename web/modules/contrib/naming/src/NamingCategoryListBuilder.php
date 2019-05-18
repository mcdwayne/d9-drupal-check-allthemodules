<?php
/**
 * @file
 * Contains Drupal\naming\NamingCategoryListBuilder.
 */

namespace Drupal\naming;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of NamingCategory entities.
 *
 * @see \Drupal\naming\Entity\RouteName
 */
class NamingCategoryListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('ID');
    $header['weight'] = $this->t('Weight');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['weight'] = $entity->getWeight();
    return $row + parent::buildRow($entity);
  }

}
