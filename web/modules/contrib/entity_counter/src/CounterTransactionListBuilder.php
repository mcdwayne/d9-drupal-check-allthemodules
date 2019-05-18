<?php

namespace Drupal\entity_counter;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of entity counter transaction entities.
 *
 * @see \Drupal\entity_counter\Entity\CounterTransaction
 */
class CounterTransactionListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The entity counter.
   *
   * @var \Drupal\entity_counter\Entity\EntityCounterInterface
   */
  protected $entityCounter;

  /**
   * Constructs a new CounterTransactionListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The current route match.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, CurrentRouteMatch $route_match, DateFormatter $date_formatter) {
    parent::__construct($entity_type, $storage);

    $this->dateFormatter = $date_formatter;
    $this->entityCounter = $route_match->getParameter('entity_counter');
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('current_route_match'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['vid'] = $this->t('Revision ID');
    $header['date'] = $this->t('Date');
    $header['log_message'] = $this->t('Log message');
    $header['value'] = $this->t('Value');
    $header['source'] = $this->t('Source');
    $header['status'] = $this->t('Status');
    $header['user'] = $this->t('User');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\entity_counter\Entity\CounterTransactionInterface $entity */
    $row['id'] = $entity->id();
    $row['vid'] = $entity->getRevisionId();
    $row['date'] = $this->dateFormatter->format($entity->getChangedTime(), 'short');
    $row['log_message'] = $entity->getRevisionLogMessage();
    $row['value'] = $entity->getTransactionValue();
    $row['source'] = $entity->getEntityCounterSource()->label();
    $row['status'] = $entity->getStatusLabel();
    $row['user'] = $entity->getRevisionUser()->label();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    /** @var \Drupal\entity_counter\Entity\CounterTransactionInterface $entity */
    if ($entity->access('update')) {
      $operations['cancel'] = [
        'title' => $this->t('Cancel'),
        'weight' => 10,
        'url' => $entity->toUrl('cancel')->setRouteParameter('entity_counter', $this->entityCounter->id()),
      ];
    }

    if (!empty($operations['edit'])) {
      $operations['edit']['attributes']['class'][] = 'use-ajax';
      $operations['edit']['attributes']['data-dialog-type'] = 'modal';
      $operations['edit']['attributes']['data-dialog-options'] = Json::encode([
        'height' => 'auto',
        'width' => 'auto',
      ]);
      $operations['edit']['url']->setRouteParameter('entity_counter', $this->entityCounter->id());
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->getTitle(),
      '#rows' => [],
      '#empty' => $this->t('No entity counter transactions available for this counter.'),
      '#cache' => [
        'contexts' => $this->entityType->getListCacheContexts(),
        'tags' => $this->entityType->getListCacheTags(),
      ],
    ];
    foreach ($this->load() as $entity) {
      if ($row = $this->buildRow($entity)) {
        $build['table']['#rows'][$entity->getRevisionId()] = $row;
      }
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $build['pager'] = [
        '#type' => 'pager',
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->condition('entity_counter.target_id', $this->entityCounter->id())
      ->condition('operation.value', CounterTransactionOperation::CANCEL, '<>')
      ->sort('revision_id', 'DESC');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

}
