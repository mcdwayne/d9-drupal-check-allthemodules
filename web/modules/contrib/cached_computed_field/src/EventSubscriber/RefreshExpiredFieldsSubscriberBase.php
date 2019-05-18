<?php

namespace Drupal\cached_computed_field\EventSubscriber;

use Drupal\cached_computed_field\Event\RefreshExpiredFieldsEventInterface;
use Drupal\cached_computed_field\ExpiredItemInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Base class for event subscribers that refresh expired cached computed fields.
 */
abstract class RefreshExpiredFieldsSubscriberBase implements RefreshExpiredFieldsSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The system time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new RefreshExpiredFieldSubscriberBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The system time service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, TimeInterface $time) {
    $this->entityTypeManager = $entityTypeManager;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [RefreshExpiredFieldsEventInterface::EVENT_NAME => [['refreshExpiredFields']]];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(ExpiredItemInterface $expiredItem) {
    return $this->entityTypeManager->getStorage($expiredItem->getEntityTypeId())->load($expiredItem->getEntityId());
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinition(ExpiredItemInterface $expiredItem) {
    return $this->getEntity($expiredItem)->getFieldDefinition($expiredItem->getFieldName());
  }

  /**
   * {@inheritdoc}
   */
  public function getExpiredFieldValue(ExpiredItemInterface $expiredItem) {
    return $this->getEntity($expiredItem)->get($expiredItem->getFieldName())->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function updateFieldValue(ExpiredItemInterface $expiredItem, $value) {
    $request_time = $this->time->getRequestTime();
    $cache_lifetime = $this->getField($expiredItem)->getSettings()['cache-max-age'];

    $entity = $this->getEntity($expiredItem);
    $entity->set($expiredItem->getFieldName(), [
      'value' => $value,
      'expire' => $request_time + $cache_lifetime,
    ]);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldNeedsRefresh(ExpiredItemInterface $expiredItem) {
    $field = $this->getField($expiredItem);
    // If the field has no value yet it is new and needs to be refreshed.
    if ($field->isEmpty()) {
      return TRUE;
    }

    // If the expire time has passed it needs to be refreshed.
    return $field->expire < $this->time->getRequestTime();
  }

  /**
   * {@inheritdoc}
   */
  public function getField(ExpiredItemInterface $expiredItem) {
    return $this->getEntity($expiredItem)->get($expiredItem->getFieldName());
  }

}
