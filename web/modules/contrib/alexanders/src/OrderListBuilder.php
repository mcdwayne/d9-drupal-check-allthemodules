<?php

namespace Drupal\alexanders;

use Drupal\alexanders\Entity\AlexandersOrder;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the list builder for orders.
 */
class OrderListBuilder extends EntityListBuilder {

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a new OrderListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager, DateFormatter $date_formatter) {
    parent::__construct($entity_type, $entity_type_manager->getStorage($entity_type->id()));

    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'order_id' => [
        'data' => $this->t('Order ID'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'due' => [
        'data' => $this->t('Due Date'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'order_number' => [
        'data' => $this->t('Order Number'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\alexanders\Entity\AlexandersOrder $orderType */
    $orderType = AlexandersOrder::load($entity->id());
    $row = [
      'order_id' => $entity->id(),
      'due_date' => $entity->getDue() ?? t('Waiting for Alexanders'),
      'order_number' => $entity->label(),
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    return $operations;
  }

}
