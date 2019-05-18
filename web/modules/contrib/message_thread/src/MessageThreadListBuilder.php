<?php

namespace Drupal\message_thread;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Message entities.
 *
 * @see \Drupal\Message\Entity\Message
 */
class MessageThreadListBuilder extends EntityListBuilder {

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateService;

  /**
   * Constructs a new NodeListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatter $date_service
   *   The date service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatter $date_service) {
    parent::__construct($entity_type, $storage);

    $this->dateService = $date_service;
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
    // Enable language column and filter if multiple languages are added.
    $header = [
      'created' => [
        'data' => $this->t('Created'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'title' => $this->t('Title'),
      'template' => [
        'data' => $this->t('Template'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'author' => [
        'data' => $this->t('Author'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'link' => [
        'data' => $this->t('View'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'edit' => [
        'data' => $this->t('Edit'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
    ];

    if (\Drupal::languageManager()->isMultilingual()) {
      $header['language_name'] = [
        'data' => $this->t('Language'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ];
    }

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {

    $url = Url::fromRoute('entity.message_thread.canonical', ['message_thread' => $entity->id()]);
    $attributes = [
      'class' => ['reports-back'],
    ];
    $link = [
      '#type' => 'link',
      '#url' => $url,
      '#title' => 'View',
      '#attributes' => $attributes,
    ];
    $url = Url::fromRoute('entity.message_thread.edit_form', ['message_thread' => $entity->id()]);
    $edit = [
      '#type' => 'link',
      '#url' => $url,
      '#title' => 'Edit',
      '#attributes' => $attributes,
    ];
    /** @var Message $entity */
    return [
      'changed' => $this->dateService->format($entity->getCreatedTime(), 'short'),
      'title' => $entity->get('field_thread_title')->getValue()[0]['value'],
      'template' => $entity->getTemplate()->label(),
      'author' => $entity->getOwner()->label(),
      'link' => render($link),
      'edit' => render($edit),
    ];
  }

}
