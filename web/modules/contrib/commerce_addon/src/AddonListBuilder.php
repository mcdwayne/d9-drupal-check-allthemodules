<?php

namespace Drupal\commerce_addon;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines the list builder for addons.
 */
class AddonListBuilder extends EntityListBuilder {

  /**
   * @inheritdoc
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Name');
    $header['price'] = $this->t('Price');
    $header['description'] = $this->t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * Builds the row.
   *
   * @inheritdoc
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_addon\Entity\AddonInterface $entity */
    $row['id'] = $entity->id();
    $row['name'] = $entity->label();
    $row['price']['data'] = [
      '#type' => 'inline_template',
      '#template' => '{{ price|commerce_price_format }}',
      '#context' => [
        'price' => $entity->getPrice(),
    ],
    ];
    $row['description'] = $entity->getDescription();

    return $row + parent::buildRow($entity);
  }

}
