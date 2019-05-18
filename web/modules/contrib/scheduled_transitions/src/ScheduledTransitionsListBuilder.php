<?php

declare(strict_types = 1);

namespace Drupal\scheduled_transitions;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Scheduled transition list builder.
 */
class ScheduledTransitionsListBuilder extends EntityListBuilder {

  /**
   * Various date related functionality.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new ScheduledTransitionsListBuilder..
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   Various date related functionality.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $dateFormatter) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $dateFormatter;
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
  public function load() {
    $query = $this->storage->getQuery();
    $header = $this->buildHeader();
    $query->tableSort($header);
    $ids = $query->execute();
    return $this->storage->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header = [
      'entity' => $this->t('Entity'),
      'date' => [
        'data' => $this->t('On date'),
        'field' => 'transition_on',
        'specifier' => 'transition_on',
        'sort' => 'asc',
      ],
    ] + parent::buildHeader();
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\scheduled_transitions\Entity\ScheduledTransitionInterface $entity */
    $row = [];

    $hostEntity = $entity->getEntity();
    try {
      $row['host_entity'] = $hostEntity ? $hostEntity->toLink() : $this->t('- Missing entity -');
    }
    catch (UndefinedLinkTemplateException $exception) {
      $row['host_entity'] = $hostEntity->label();
    }

    // Date.
    $time = $entity->getTransitionTime();
    $row['date'] = $this->dateFormatter->format($time);

    return $row + parent::buildRow($entity);
  }

}
