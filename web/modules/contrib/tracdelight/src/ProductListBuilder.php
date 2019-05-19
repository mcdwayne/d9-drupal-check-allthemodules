<?php

/**
 * @file
 * Contains Drupal\tracdelight\ProductListBuilder.
 */

namespace Drupal\tracdelight;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Product entities.
 *
 * @ingroup tracdelight
 */
class ProductListBuilder extends EntityListBuilder {
  use LinkGeneratorTrait;
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Product ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\tracdelight\Entity\Product */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $this->getLabel($entity),
      new Url(
        'entity.product.edit_form', array(
          'product' => $entity->id(),
        )
      )
    );
    return $row + parent::buildRow($entity);
  }

}
