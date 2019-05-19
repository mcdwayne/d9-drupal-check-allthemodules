<?php

namespace Drupal\webform_cart;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Webform cart item entities.
 *
 * @ingroup webform_cart
 */
class WebformCartItemListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Webform cart item ID');
//    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\webform_cart\Entity\WebformCartItem */
    $row['id'] = $entity->id();
//    $row['name'] = Link::createFromRoute(
//      $entity->label(),
//      'entity.webform_cart_item.edit_form',
//      ['webform_cart_item' => $entity->id()]
//    );
    return $row + parent::buildRow($entity);
  }

}
