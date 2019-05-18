<?php

namespace Drupal\entity_counter;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity_counter\Entity\EntityCounterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of entity counter transaction log.
 *
 * @see \Drupal\entity_counter\Entity\CounterTransaction
 */
class CounterTransactionLogListBuilder extends EntityListBuilder {

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
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   * @param \Drupal\entity_counter\Entity\EntityCounterInterface $entity_counter
   *   The entity counter.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatter $date_formatter, EntityCounterInterface $entity_counter = NULL) {
    parent::__construct($entity_type, $storage);

    $this->dateFormatter = $date_formatter;
    $this->entityCounter = $entity_counter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['vid'] = $this->t('Version ID');
    $header['date'] = $this->t('Date');
    $header['log_message'] = $this->t('Log message');
    $header['value'] = $this->t('Value');
    $header['source'] = $this->t('Source');
    $header['status'] = $this->t('Status');
    $header['user'] = $this->t('User');

    // @TODO Add operations and allow to re-queue exceeded or canceled items.
    return $header;
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

    return $row;
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
  public function load() {
    $entity_vids = array_keys($this->getEntityIds());

    // @TODO https://www.drupal.org/project/drupal/issues/1730874
    if (method_exists($this, 'loadMultipleRevisions')) {
      $revisions = $this->storage->loadMultipleRevisions($entity_vids);
    }
    else {
      $revisions = [];
      foreach ($entity_vids as $entity_vid) {
        $revisions[] = $this->storage->loadRevision($entity_vid);
      }
    }

    return $revisions;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->condition('entity_counter.target_id', $this->entityCounter->id())
      ->allRevisions()
      ->sort('revision_id', 'DESC');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

}
