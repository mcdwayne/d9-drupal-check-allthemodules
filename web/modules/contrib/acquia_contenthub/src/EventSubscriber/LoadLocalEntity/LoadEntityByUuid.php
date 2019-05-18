<?php

namespace Drupal\acquia_contenthub\EventSubscriber\LoadLocalEntity;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\LoadLocalEntityEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Acquia\ContentHubClient\CDF\CDFObject;

/**
 * Class LoadEntityByUuid.
 *
 * Loads a Local Entity by UUID.
 *
 * @package Drupal\acquia_contenthub\EventSubscriber\LoadLocalEntity
 */
class LoadEntityByUuid implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::LOAD_LOCAL_ENTITY][] = ['onLoadLocalEntity', 10];
    return $events;
  }

  /**
   * Reacts to local entity load events.
   *
   * @param \Drupal\acquia_contenthub\Event\LoadLocalEntityEvent $event
   *   The local entity loading event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function onLoadLocalEntity(LoadLocalEntityEvent $event) {
    $cdf = $event->getCdf();
    $entity_type_id = $cdf->getAttribute('entity_type')->getValue()[CDFObject::LANGUAGE_UNDETERMINED];
    if ($entity = $this->getEntityRepository()->loadEntityByUuid($entity_type_id, $cdf->getUuid())) {
      $event->setEntity($entity);
      $event->stopPropagation();
    }
  }

  /**
   * Gets the entity repository.
   *
   * @return \Drupal\Core\Entity\EntityRepositoryInterface
   *   The Entity Repository Service.
   */
  protected function getEntityRepository() {
    return \Drupal::service('entity.repository');
  }

}
