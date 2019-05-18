<?php

namespace Drupal\shopify;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Shopify product variant entities.
 *
 * @ingroup shopify
 */
class ShopifyProductVariantListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Shopify product variant ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\shopify\Entity\ShopifyProductVariant */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute($entity->label(), 'entity.shopify_product_variant.edit_form', ['shopify_product_variant' => $entity->id()]);
    return $row + parent::buildRow($entity);
  }

}
