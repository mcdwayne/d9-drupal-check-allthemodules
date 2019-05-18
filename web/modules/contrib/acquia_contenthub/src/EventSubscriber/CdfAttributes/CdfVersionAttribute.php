<?php

namespace Drupal\acquia_contenthub\EventSubscriber\CdfAttributes;

use Acquia\ContentHubClient\CDF\CDFObject;
use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\BuildClientCdfEvent;
use Drupal\acquia_contenthub\Event\CdfAttributesEvent;
use Drupal\Core\Entity\TranslatableInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Notates the metadata with a CDF version marker.
 */
class CdfVersionAttribute implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::POPULATE_CDF_ATTRIBUTES][] = ['onPopulateAttributes', 100];
    $events[AcquiaContentHubEvents::BUILD_CLIENT_CDF][] = ['onBuildClientCdf', 100];

    return $events;
  }

  /**
   * Adds a version attribute to Cdf Objects.
   *
   * @param \Drupal\acquia_contenthub\Event\CdfAttributesEvent $event
   *   The event being dispatched.
   *
   * @throws \Exception
   */
  public function onPopulateAttributes(CdfAttributesEvent $event) {
    $entity = $event->getEntity();
    if ($entity instanceof TranslatableInterface) {
      $cdf = $event->getCdf();
      $this->setVersion($cdf);
    }
  }

  /**
   * Adds a version attribute to Cdf Objects.
   *
   * @param \Drupal\acquia_contenthub\Event\BuildClientCdfEvent $event
   */
  public function onBuildClientCdf(BuildClientCdfEvent $event) {
    $cdf = $event->getCdf();
    $this->setVersion($cdf);
  }

  /**
   * Internal function to set the version for a CDF.
   *
   * @param \Acquia\ContentHubClient\CDF\CDFObject $cdf
   */
  protected function setVersion(CDFObject $cdf) {
    $metadata = $cdf->getMetadata();
    $metadata['version'] = 2;
    $cdf->setMetadata($metadata);
  }
}
