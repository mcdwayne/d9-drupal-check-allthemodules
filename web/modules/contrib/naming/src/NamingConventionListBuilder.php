<?php
/**
 * @file
 * Contains Drupal\naming\NamingConventionListBuilder.
 */

namespace Drupal\naming;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\naming\Entity\NamingCategory;

/**
 * Defines a class to build a listing of NamingConvention entities.
 *
 * @see \Drupal\naming\Entity\RouteName
 */
class NamingConventionListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Route/Custom ID');
    $header['category'] = $this->t('Category');
    $header['weight'] = $this->t('Weight');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $category = NamingCategory::load($entity->getCategory());
    $row['label'] = $entity->label();
    $row['id'] = ($url = $entity->getRouteUrl()) ? Link::fromTextAndUrl($entity->id(), $url) : $entity->id();
    $row['category'] = ($category) ? $category->label() : $entity->getCategory();
    $row['weight'] = $entity->getWeight();
    return $row + parent::buildRow($entity);
  }

}
