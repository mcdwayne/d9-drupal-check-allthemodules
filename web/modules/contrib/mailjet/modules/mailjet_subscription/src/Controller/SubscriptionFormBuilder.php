<?php

namespace Drupal\mailjet_subscription\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Mailjet Subscription form entities.
 *
 *
 * @ingroup mailjet_subscription_form
 */
class SubscriptionFormBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'mailjet_subscription_form';
  }

  /**
   * Builds the header row for the entity listing.
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['machine_name'] = $this->t('ID');
    $header['created'] = $this->t('Created');
    $header['changed'] = $this->t('Changed');

    return $header + parent::buildHeader();
  }

  /**
   * Builds a row for an entity in the entity listing.
   *
   */
  public function buildRow(EntityInterface $entity) {

    $row['name'] = $entity->name;
    $row['machine_name'] = $entity->id();
    $row['created'] = $entity->created_date;
    $row['changed'] = $entity->changed_date;

    return $row + parent::buildRow($entity);
  }

  /**
   * Adds some descriptive text to our entity list.
   *
   *
   * @return array
   *   Renderable array.
   */
  public function render() {
    $build[] = parent::render();
    return $build;
  }

}