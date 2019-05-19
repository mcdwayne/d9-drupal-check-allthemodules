<?php

/**
 * @file
 * Contains \Drupal\temporal\TemporalListBuilder.
 */

namespace Drupal\temporal;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Routing\RedirectDestination;

/**
 * Defines a class to build a listing of Temporal entities.
 *
 * @ingroup temporal
 */
class TemporalListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    // Grab context so we can customize the columns by entity type
    list($entity_type, $entity_id) = $this->determineEntityContext();

    $header['id'] = [
      'data' => $this->t('Temporal ID'),
      'field' => 'id',
      'specifier' => 'id',
      'class' => array(RESPONSIVE_PRIORITY_LOW),
    ];
    $header['delta'] = [
      'data' => $this->t('Delta'),
    ];
    $header['future'] = [
      'data' => $this->t('Future'),
    ];
    $header['value'] = [
      'data' => $this->t('Value'),
    ];

    switch($entity_type) {
      case 'node':
        //$header['entity_id'] = $this->t('Node ID');

        break;

      case 'user':
        //$header['entity_id'] = $this->t('User ID');

        break;

      default:
        $header['entity_id'] = [
          'data' => $this->t('Entity ID'),
          'field' => 'entity_id',
          'specifier' => 'entity_id',
        ];
        $header['entity_type'] = [
          'data' => $this->t('Type'),
          'field' => 'entity_type',
          'specifier' => 'entity_type',
        ];
        //$this->t('Type');
        $header['entity_bundle'] = [
          'data' => $this->t('Bundle'),
        ];
        $header['entity_field'] = [
          'data' => $this->t('Field'),
        ];
    }
    $header['created'] = [
      'data' => $this->t('Created'),
      'field' => 'created',
      'specifier' => 'created',
      'sort' => 'desc',
    ];

    return $header + parent::buildHeader();
  }

  public function load() {
    // Grab context so we can customize the columns by entity type
    list($entity_type, $entity_id) = $this->determineEntityContext();

    /** @var QueryInterface $entity_query */
    $entity_query = \Drupal::service('entity.query')->get('temporal');
    $header = $this->buildHeader();

    $entity_query->pager(50);
    $entity_query->tableSort($header);
    // Ensure we only show the entries for the entity being viewed
    if($entity_type) {
      $entity_query->condition('entity_type', $entity_type);
    }
    if($entity_id) {
      $entity_query->condition('entity_id', $entity_id);
    }


    $ids = $entity_query->execute();

    return $this->storage->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    // Grab context so we can customize the columns by entity type
    list($entity_type, $entity_id) = $this->determineEntityContext();

    /* @var $entity \Drupal\temporal\Entity\Temporal */
    $row['id'] = $entity->id();
    $row['delta'] = $entity->getDelta();
    $row['future'] = $entity->getFuture();
    $row['value'] = $entity->renderValue();
    $entity_type = $entity->getTemporalEntityType();
    $bundle = $entity->getTemporalEntityBundle();
    $field = $entity->getTemporalEntityField();
    $field_type = $entity->getTemporalEntityFieldType();

    switch($entity_type) {
      case 'node':
        //$row['entity_id'] = $entity->getEntityId();

        break;

      case 'user':
        //$row['entity_id'] = $entity->getEntityId();

        break;

      default:
        $row['entity_id'] = $entity->getEntityId();
        $row['entity_type'] = $entity_type;
        $row['entity_bundle'] = $bundle;
        $row['entity_field'] = $field;
    }

    $row['created'] = \Drupal::service('date.formatter')->format($entity->getCreatedTime());

    return $row + parent::buildRow($entity);
  }

  /**
   * Loads entity IDs using a pager sorted by the entity id.
   *
   * @return array
   *   An array of entity IDs.
   */
  protected function getEntityIds() {
    list($entity_type, $entity_id) = $this->determineEntityContext();

    $query = $this->getStorage()->getQuery()
      ->sort($this->entityType->getKey('id'));

    // Filter to show only the temporal entries on the {node|user}/*/temporal/history page
    if($entity_id > 0) {
      $query->condition('entity_type', $entity_type)
        ->condition('entity_id', $entity_id);
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }


  /**
   * Common function to examine the route for user/* OR node/*
   * @return array
   */
  private function determineEntityContext() {
    $params = \Drupal::routeMatch()->getParameters();
    if ($entity_id = $params->get('node')) {
      $entity_type = 'node';
    }
    elseif ($entity_id = $params->get('user')) {
      $entity_type = 'user';
    }
    else {
      $entity_type = NULL;
      $entity_id = NULL;
    }

    return array($entity_type, $entity_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    /** @var RedirectDestination $redirectService */
    $redirectService = \Drupal::service('redirect.destination');
    $destination = $redirectService->getAsArray();
    foreach ($operations as $key => $operation) {
      $operations[$key]['url']->setOption('query', $destination);
    }
    return $operations;
  }
}
