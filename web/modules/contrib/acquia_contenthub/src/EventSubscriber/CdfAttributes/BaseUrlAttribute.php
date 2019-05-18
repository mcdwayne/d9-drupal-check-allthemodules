<?php

namespace Drupal\acquia_contenthub\EventSubscriber\CdfAttributes;

use Acquia\ContentHubClient\CDFAttribute;
use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\CdfAttributesEvent;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds base url to all CDF documents.
 */
class BaseUrlAttribute implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::POPULATE_CDF_ATTRIBUTES][] = ['onPopulateAttributes', 100];
    return $events;
  }

  /**
   * Adds base url to all CDF documents.
   *
   * @param \Drupal\acquia_contenthub\Event\CdfAttributesEvent $event
   *   The event being dispatched.
   *
   * @throws \Exception
   */
  public function onPopulateAttributes(CdfAttributesEvent $event) {
    $cdf = $event->getCdf();
    $url = Url::fromUserInput("/", ['absolute' => TRUE]);
    $cdf->addAttribute('base_url', CDFAttribute::TYPE_STRING, $url->toString());
  }

}
