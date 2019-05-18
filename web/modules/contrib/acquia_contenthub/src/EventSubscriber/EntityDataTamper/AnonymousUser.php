<?php

namespace Drupal\acquia_contenthub\EventSubscriber\EntityDataTamper;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\EntityDataTamperEvent;
use Drupal\depcalc\DependentEntityWrapper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Replace anonymous user with the local anonymous user.
 */
class AnonymousUser implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::ENTITY_DATA_TAMPER][] = 'onDataTamper';
    return $events;
  }

  /**
   * Tamper with CDF data before its imported.
   *
   * @param \Drupal\acquia_contenthub\Event\EntityDataTamperEvent $event
   *   The data tamper event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onDataTamper(EntityDataTamperEvent $event) {
    foreach ($event->getCdf()->getEntities() as $uuid => $object) {
      $entity_type = $object->getAttribute('entity_type');
      if ($entity_type && $entity_type->getValue()['und'] == 'user') {
        $anonymous = $object->getAttribute('is_anonymous');
        // The attribute won't be present if the user is not anonymous.
        if ($anonymous) {
          $entity = \Drupal::entityTypeManager()->getStorage('user')->load(0);
          $wrapper = new DependentEntityWrapper($entity);
          $wrapper->setRemoteUuid($uuid);
          $event->getStack()->addDependency($wrapper);
        }
      }
    }
  }

}
