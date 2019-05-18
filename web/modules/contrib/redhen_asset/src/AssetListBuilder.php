<?php

/**
 * @file
 * Contains \Drupal\redhen_asset\AssetListBuilder.
 */

namespace Drupal\redhen_asset;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Asset entities.
 *
 * @ingroup redhen_asset
 */
class AssetListBuilder extends EntityListBuilder {
  use LinkGeneratorTrait;
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['type'] = $this->t('Type');
    $header['name'] = $this->t('Name');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\redhen_asset\Entity\Asset */
    $row['type'] = $entity->getType();
    $row['name'] = $entity->link();
    $row['status'] = $entity->isActive() ? $this->t('Active') : $this->t('Inactive');
    return $row + parent::buildRow($entity);
  }

}
