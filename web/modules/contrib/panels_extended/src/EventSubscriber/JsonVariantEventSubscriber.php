<?php

namespace Drupal\panels_extended\EventSubscriber;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\panels_extended\Event\JsonDisplayVariantBuildEvent;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Changes the JSON output of the display variant.
 */
class JsonVariantEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[JsonDisplayVariantBuildEvent::ALTER_BUILD][] = ['alterBuild', -10];
    return $events;
  }

  /**
   * Alters the JSON output by adding page configuration values.
   *
   * @param \Drupal\panels_extended\Event\JsonDisplayVariantBuildEvent $event
   *   The event.
   */
  public function alterBuild(JsonDisplayVariantBuildEvent $event) {
    $build = &$event->getBuild();

    $pageConfig['title'] = PlainTextOutput::renderFromHtml($build['#title']);
    unset($build['#title']);

    $contexts = $event->getDisplay()->getContexts();
    if (isset($contexts['taxonomy_term'])) {
      $term = $contexts['taxonomy_term']->getContextValue();
      if ($term instanceof TermInterface) {
        $pageConfig['term_id'] = (int) $term->id();
      }
    }

    $build['#configuration'] = $pageConfig;
  }

}
