<?php

namespace Drupal\cached_computed_field\EventSubscriber;

use Drupal\cached_computed_field\Event\RefreshExpiredFieldsEventInterface;
use Drupal\cached_computed_field\ExpiredItemInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Interface for event subscribers that refresh expired cached computed fields.
 */
interface RefreshExpiredFieldsSubscriberInterface extends EventSubscriberInterface {

  /**
   * Event subscriber that reacts to the RefreshExpiredFieldsEvent.
   *
   * @param \Drupal\cached_computed_field\Event\RefreshExpiredFieldsEventInterface $event
   *   The triggering event.
   */
  public function refreshExpiredFields(RefreshExpiredFieldsEventInterface $event);

  /**
   * Returns the entity that contains the expired field.
   *
   * @param \Drupal\cached_computed_field\ExpiredItemInterface $expiredItem
   *   The expired field item.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The entity.
   */
  public function getEntity(ExpiredItemInterface $expiredItem);

  /**
   * Returns the field definition of the expired field.
   *
   * @param \Drupal\cached_computed_field\ExpiredItemInterface $expiredItem
   *   The expired field item.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The definition of the field.
   */
  public function getFieldDefinition(ExpiredItemInterface $expiredItem);

  /**
   * Returns the expired data that is currently cached in the field.
   *
   * @param \Drupal\cached_computed_field\ExpiredItemInterface $expiredItem
   *   The expired field item.
   *
   * @return mixed
   *   The data.
   */
  public function getExpiredFieldValue(ExpiredItemInterface $expiredItem);

  /**
   * Updates the field with the given value, and sets the cache expiration time.
   *
   * Call this with the calculated value. It will write the value to the
   * database and reset the cache lifetime.
   *
   * @param \Drupal\cached_computed_field\ExpiredItemInterface $expiredItem
   *   The expired field item.
   * @param mixed $value
   *   The value to set.
   */
  public function updateFieldValue(ExpiredItemInterface $expiredItem, $value);

  /**
   * Returns whether or not the field needs to be refreshed.
   *
   * It is possible a field value has already been refreshed in the time between
   * it was added to the queue and the moment the event fires.
   *
   * @param \Drupal\cached_computed_field\ExpiredItemInterface $expiredItem
   *   The expired field item.
   *
   * @return bool
   *   TRUE if the cache lifetime of the field has expired, or if the field is
   *   new and has not been populated with a value yet.
   */
  public function fieldNeedsRefresh(ExpiredItemInterface $expiredItem);

  /**
   * Returns the expired field instance.
   *
   * @param \Drupal\cached_computed_field\ExpiredItemInterface $expiredItem
   *   The expired field item.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   The field instance.
   */
  public function getField(ExpiredItemInterface $expiredItem);

}
