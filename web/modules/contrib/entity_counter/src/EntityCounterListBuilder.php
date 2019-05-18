<?php

namespace Drupal\entity_counter;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of entity counter entities.
 *
 * @see \Drupal\entity_counter\Entity\EntityCounter
 */
class EntityCounterListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['value'] = $this->t('Value');
    $header['status'] = $this->t('Status');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\entity_counter\Entity\EntityCounterInterface $entity */
    $row['label']['data'] = Link::createFromRoute(
      $entity->label(),
      'entity.entity_counter.canonical',
      ['entity_counter' => $entity->id()]
    );
    $row['id'] = $entity->id();
    $row['value'] = $entity->getValue();
    $row['status'] = $entity->isOpen() ? $this->t('Open') : $this->t('Closed');

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\entity_counter\Entity\EntityCounterInterface $entity */
    $operations = parent::getDefaultOperations($entity);

    foreach (['edit', 'delete'] as $operation) {
      if (!empty($operations[$operation])) {
        $operations[$operation]['attributes']['class'][] = 'use-ajax';
        $operations[$operation]['attributes']['data-dialog-type'] = 'modal';
        $operations[$operation]['attributes']['data-dialog-options'] = Json::encode([
          'height' => 'auto',
          'width' => 'auto',
        ]);
      }
    }

    $operations['transactions'] = [
      'title' => $this->t('Transactions'),
      'url' => Url::fromRoute('entity.entity_counter_transaction.collection', ['entity_counter' => $entity->id()]),
      'weight' => 50,
    ];
    $operations['log'] = [
      'title' => $this->t('Log'),
      'url' => Url::fromRoute('entity.entity_counter_transaction.log', ['entity_counter' => $entity->id()]),
      'weight' => 51,
    ];
    $operations['remove_transactions'] = [
      'title' => $this->t('Remove all ransactions'),
      'url' => Url::fromRoute('entity.entity_counter.remove_transactions_form', ['entity_counter' => $entity->id()]),
      'weight' => 150,
    ];

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $build['table']['#empty'] = $this->t('No entity counters available. <a href=":link">Add an entity counter</a>.', [
      ':link' => Url::fromRoute('entity.entity_counter.add_form')->toString(),
    ]);

    return $build;
  }

}
