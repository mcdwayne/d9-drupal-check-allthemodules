<?php

namespace Drupal\simple_global_filter\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class InitSubscriber.
 */
class InitSubscriber implements EventSubscriberInterface {


  /**
   * Constructs a new InitSubscriber object.
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    // Run at least before than RedirectRequestSubscriber (33).
    $events[KernelEvents::REQUEST] = ['setFilterIfInURL', 34];

    return $events;
  }

  /**
   * Checks if a global filter comes in the URL. If so, set its value
   *
   * @param GetResponseEvent $event
   */
  public function setFilterIfInURL(Event $event) {
    // Check which global filters are in the URL.
    $global_filters = \Drupal::entityQuery('global_filter')->execute();
    foreach ($global_filters as $global_filter_id) {
      if ($value = \Drupal::request()->get($global_filter_id)) {
        // Check if the global filter uses alias:
        $global_filter = \Drupal::entityTypeManager()->getStorage('global_filter')->
           load($global_filter_id);
        if ($alias_field = $global_filter->getAliasField()) {
          // Get the actual value from the alias:
          $result = \Drupal::entityQuery('taxonomy_term')->
              condition('vid', $global_filter->getVocabulary())->
              condition($alias_field, $value)->execute();
          if (count($result)) {
            \Drupal::service('simple_global_filter.global_filter')->
              set($global_filter_id, current($result));
          }
          else {
            // There is not any taxonomy_term with this alias, set term id:
            \Drupal::service('simple_global_filter.global_filter')->set($global_filter_id, $value);
          }
        }
        else {
          \Drupal::service('simple_global_filter.global_filter')->set($global_filter_id, $value);
        }
      }
    }
  }
}
