<?php

namespace Drupal\sfweb2lead_webform\EventSubscriber;

use Drupal\sfweb2lead_webform\Event\Sfweb2leadWebformEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class BackwardsCompatibilitySubscriber
 *
 * @deprecated Backwards compatibility only. Do not use.
 */
class BackwardsCompatibilitySubscriber implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    $events[Sfweb2leadWebformEvent::SUBMIT][] = ['doHookAlter', 800];
    return $events;
  }

  public function doHookAlter(Sfweb2leadWebformEvent $event) {
    $implements = \Drupal::moduleHandler()->getImplementations('sfweb2lead_webform_posted_data_alter');

    if (empty($implements)) {
      return;
    }

    $logger = \Drupal::logger('sfweb2lead_webform');
    $logger->warning('Use of `hook_sfweb2lead_webform_posted_data_alter` has been deprecated. Implement an event subscriber instead.');

    $salesforce_data = $event->getData();
    $webform = $event->getHandler()->getWebform();
    $webform_submission = $event->getSubmission();

    \Drupal::moduleHandler()->alter('sfweb2lead_webform_posted_data', $salesforce_data, $webform, $webform_submission);

    foreach ($salesforce_data as $key => $value) {
      $salesforce_data[$key] = $value;
    }
    $event->setData($salesforce_data);
  }

}
