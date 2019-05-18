<?php

namespace Drupal\mass_contact;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mass contact message archive list builder.
 */
class MessageListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Mass contact message list builder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $date_formatter;
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
    $header = [
      'subject' => [
        'data' => $this->t('Subject'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'sent' => [
        'data' => $this->t('Sent on'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'uid' => [
        'data' => $this->t('Sent by'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'categories' => [
        'data' => $this->t('Categories'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];
    $header += parent::buildHeader();
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    // Build a list of categories.
    $categories = [];

    foreach ($entity->getCategories() as $category) {
      // It is possible to delete a category after creating a mass contact
      // message for that category.
      if (!empty($category)) {
        $categories[] = $category->label();
      }
    };

    /** @var \Drupal\mass_contact\Entity\MassContactMessageInterface $entity */
    $row = [
      'subject' => $entity->toLink(),
      'sent' => $this->dateFormatter->format($entity->getSentTime(), 'short'),
      'uid' => $entity->getOwner()->toLink(),
      'categories' => [
        'data' => [
          '#theme' => 'item_list',
          '#items' => $categories,
        ],
      ],
    ];
    $row += parent::buildRow($entity);
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    if ($entity->access('view') && $entity->hasLinkTemplate('canonical')) {
      $operations['view'] = [
        'title' => $this->t('View'),
        'weight' => 10,
        'url' => $entity->toUrl(),
      ];
    }
    return $operations;
  }

}
