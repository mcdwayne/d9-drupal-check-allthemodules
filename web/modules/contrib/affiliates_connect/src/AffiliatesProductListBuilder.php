<?php

namespace Drupal\affiliates_connect;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Affiliates Product entities.
 *
 * @ingroup affiliates_connect
 */
class AffiliatesProductListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Affiliates Product ID');
    $header['name'] = $this->t('Name');
    $header['product_description'] = $this->t('Description');
    $header['plugin'] = $this->t('Vendor');
    $header['product_id'] = $this->t('Product ID');
    $header['price'] = $this->t('Price');
    $header['availability'] = $this->t('Availability');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\affiliates_connect\Entity\AffiliatesProduct */
    $row['id'] = $entity->id();
    $row['name'] = [
      'data' => [
        '#prefix' => '<div class="affiliates-connect-inline"><img src="' . $entity->getImageUrls() . '" width=30 height=40> &nbsp;&nbsp;',
        '#suffix' => '<a href="' . $entity->getProductUrl() . '">' . $entity->label() . '</a></div>',
      ],
    ];
    $row['product_description'] = $entity->getProductDescription();
    $row['plugin'] = $entity->getPluginId();
    $row['product_id'] = $entity->getProductId();
    $row['price'] = [
      'data' => [
        '#prefix' => '<div> <b>M.R.P : </b>' . $entity->getCurrency() . $entity->getMaximumRetailPrice() . '</div> <div> <b> Selling Price : </b>' . $entity->getCurrency() . $entity->getVendorSellingPrice() . '</div> <b> Special Price : </b>' . $entity->getCurrency() . $entity->getVendorSpecialPrice(),
        '#suffix' => '</div>',
      ],
    ];
    $row['availability'] = ($entity->getProductAvailability()) ? 'In Stock' : 'Out of Stock';
    return $row + parent::buildRow($entity);
  }

}
