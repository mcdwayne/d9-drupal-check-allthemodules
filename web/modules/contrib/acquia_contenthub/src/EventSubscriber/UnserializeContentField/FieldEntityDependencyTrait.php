<?php

namespace Drupal\acquia_contenthub\EventSubscriber\UnserializeContentField;

use Drupal\acquia_contenthub\Event\UnserializeCdfEntityFieldEvent;

/**
 * Trait FieldEntityDependencyTrait.
 *
 * @package Drupal\acquia_contenthub\EventSubscriber\UnserializeContentField
 */
trait FieldEntityDependencyTrait {

  /**
   * Get an entity from the dependency stack.
   *
   * @param string $uuid
   *   The uuid of the entity to retrieve.
   * @param \Drupal\acquia_contenthub\Event\UnserializeCdfEntityFieldEvent $event
   *   The subscribed event.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The retrieved entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntity($uuid, UnserializeCdfEntityFieldEvent $event) {
    // Check stack first because uuids are referenced from origin, not local.
    if ($event->getStack()->hasDependency($uuid)) {
      return $event->getStack()->getDependency($uuid)->getEntity();
    }
    // Only fall back to local uuids as an absolute last resort.
    if (empty($event->getFieldMetadata()['target'])) {
      throw new \Exception(sprintf("The %s field does not specify a metadata target. This is likely due to an unresolved dependency export process. Please check your relationships.", $event->getFieldName()));
    }
    $storage = \Drupal::entityTypeManager()->getStorage($event->getFieldMetadata()['target']);
    $entities = $storage->loadByProperties(['uuid' => $uuid]);
    return reset($entities);
  }

}
