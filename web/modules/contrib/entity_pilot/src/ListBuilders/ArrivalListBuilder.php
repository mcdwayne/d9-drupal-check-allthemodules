<?php

namespace Drupal\entity_pilot\ListBuilders;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of arrival entities.
 *
 * @see \Drupal\entity_pilot\Entity\Arrival
 */
class ArrivalListBuilder extends EntityListBuilder {

  /**
   * Array of state values.
   *
   * @var array
   */
  protected $states = [];

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['ID'] = $this->t('ID');
    $header['label'] = $this->t('Description');
    $header['contents'] = $this->t('Contents');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['label'] = $this->getLabel($entity);
    $row['contents']['data'] = [
      'contents' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->formatPlural(count($entity->getPassengers()), '1 item', '@count items'),
        ],
      ],
    ];
    if (empty($this->states)) {
      $this->states = $this->storage->getAllowedStates();
    }
    $row['status'] = $this->states[$entity->getStatus()];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    if ($entity->access('view') && $entity->hasLinkTemplate('canonical')) {
      $operations['view'] = [
        'title' => $this->t('View'),
        'weight' => 15,
        'url' => $entity->toUrl('canonical'),
      ];
    }
    if ($entity->access('approve') && $entity->hasLinkTemplate('approve-form')) {
      $operations['view'] = [
        'title' => $this->t('Approve'),
        'weight' => -10,
        'url' => $entity->toUrl('approve-form'),
      ];
    }
    if ($entity->access('queue') && $entity->hasLinkTemplate('queue-form')) {
      $operations['view'] = [
        'title' => $this->t('Queue'),
        'weight' => -10,
        'url' => $entity->toUrl('queue-form'),
      ];
    }

    return $operations;
  }

}
