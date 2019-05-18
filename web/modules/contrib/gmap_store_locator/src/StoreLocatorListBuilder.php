<?php

namespace Drupal\store_locator;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Store locator entities.
 *
 * @ingroup store_locator
 */
class StoreLocatorListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Store Name');
    $header['city'] = $this->t('City');
    $header['address'] = $this->t('Address');
    $header['postcode'] = $this->t('Postcode');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\store_locator\Entity\StoreLocator */
    $row['name'] = $this->l($entity->label(), new Url('entity.store_locator.canonical', [
      'store_locator' => $entity->id(),
    ]));
    $row['city'] = $entity->get('city')->value;
    $row['address'] = $entity->get('address_one')->value . ', ' . $entity->get('address_two')->value;
    $row['postcode'] = $entity->get('postcode')->value;
    return $row + parent::buildRow($entity);
  }

}
