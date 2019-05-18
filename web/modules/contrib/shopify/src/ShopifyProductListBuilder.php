<?php

namespace Drupal\shopify;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Shopify product entities.
 *
 * @ingroup shopify
 */
class ShopifyProductListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Entity ID');
    $header['product_id'] = $this->t('Product ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\shopify\Entity\ShopifyProduct */
    $row['id'] = $entity->id();
    $row['product_id'] = $entity->product_id->value;
    $row['name'] = Link::createFromRoute($entity->label(), 'entity.shopify_product.canonical', ['shopify_product' => $entity->id()]);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    $edit['edit_on_shopify'] = [
      'title' => t('Edit on Shopify'),
      'url' => Url::fromUri('https://' . shopify_shop_info('domain') . '/admin/products/' . $entity->product_id->value, ['attributes' => ['target' => '_blank']]),
      'weight' => 5,
    ];
    array_unshift($operations, $edit['edit_on_shopify']);
    return $operations;
  }

}
