<?php

namespace Drupal\subscription_entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Subscription entities.
 *
 * @ingroup subscription
 */
class SubscriptionListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Subscription ID');
    $header['subscription_ref'] = $this->t('Subscription ref');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\subscription_entity\Entity\subscription */
    $row['id'] = $entity->id();
    $row['subscription_ref'] = $this->l(
      $entity->label(),
      new Url(
        'entity.subscription.canonical', array(
          'subscription' => $entity->id(),
        )
      )
    );
    return $row + parent::buildRow($entity);
  }

}
