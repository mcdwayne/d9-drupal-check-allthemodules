<?php

namespace Drupal\acquia_contenthub\EventSubscriber\CdfAttributes;

use Acquia\ContentHubClient\CDFAttribute;
use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\BuildClientCdfEvent;
use Drupal\acquia_contenthub\Event\CdfAttributesEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Calculates a hash value of the entity and stores it as an attribute.
 */
class HashCdfAttribute implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::POPULATE_CDF_ATTRIBUTES][] = ['onPopulateAttributes', 100];
    $events[AcquiaContentHubEvents::BUILD_CLIENT_CDF][] = ['onBuildClientCdf', -100];

    return $events;
  }

  /**
   * On populate attributes.
   *
   * @param \Drupal\acquia_contenthub\Event\CdfAttributesEvent $event
   *   CDF attributes event.
   *
   * @throws \Exception
   */
  public function onPopulateAttributes(CdfAttributesEvent $event) {
    $entity = $event->getEntity();
    $cdf = $event->getCdf();
    $cdf->addAttribute('hash', CDFAttribute::TYPE_STRING, $this->getHash($entity->toArray()));
  }

  /**
   * On Build ClientCdf.
   *
   * @param \Drupal\acquia_contenthub\Event\BuildClientCdfEvent $event
   *
   * @throws \Exception
   */
  public function onBuildClientCdf(BuildClientCdfEvent $event) {
    $cdf = $event->getCdf();
    $cdf->addAttribute('hash', CDFAttribute::TYPE_STRING, $this->getHash($cdf->getMetadata()));
    $event->stopPropagation();
  }

  /**
   * Internal function to return the hash of some arbitrary array of values.
   *
   * @param array $values
   *
   * @return string
   */
  protected function getHash(array $values) {
    return sha1(json_encode($values));
  }
}
