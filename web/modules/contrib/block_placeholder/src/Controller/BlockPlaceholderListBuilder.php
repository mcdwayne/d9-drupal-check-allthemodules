<?php

namespace Drupal\block_placeholder\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Define block placeholder list builder.
 */
class BlockPlaceholderListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Identifier');
    $header['label'] = $this->t('Label');
    $header['block_types'] = $this->t('Block Types');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];
    $row['id'] = $entity->id();
    $row['label'] = $entity->label();
    $block_types = $entity->blockTypes();

    if (empty($block_types)) {
      $element_type = $this->t('All');
    }
    else {
      $element_type = [
        '#theme' => 'item_list',
        '#items' => $block_types,
      ];
    }
    $row['block_types'] = render($element_type);

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    $operations['order'] = [
      'title' => $this->t('Order'),
      'weight' => 10,
      'url' => Url::fromRoute('block_placeholder.order_form', [
        $entity->getEntityTypeId() => $entity->id()
      ]),
    ];

    return $operations;
  }
}
