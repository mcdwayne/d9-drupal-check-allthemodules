<?php

namespace Drupal\webform_cart;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Webform cart order entities.
 *
 * @ingroup webform_cart
 */
class WebformCartOrderListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Webform cart order ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\webform_cart\Entity\WebformCartOrder */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.webform_cart_order.edit_form',
      ['webform_cart_order' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
