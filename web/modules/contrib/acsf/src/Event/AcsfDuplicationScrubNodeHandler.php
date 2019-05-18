<?php

namespace Drupal\acsf\Event;

/**
 * Handles the scrubbing of Drupal nodes.
 */
class AcsfDuplicationScrubNodeHandler extends AcsfDuplicationScrubEntityHandler {

  /**
   * Constructor.
   *
   * @param AcsfEvent $event
   *   The event that has been initiated.
   */
  public function __construct(AcsfEvent $event) {
    $this->entityTypeId = 'node';
    parent::__construct($event);
  }

  /**
   * Implements AcsfEventHandler::handle().
   */
  public function handle() {
    $options = $this->event->context['scrub_options'];
    if ($options['retain_content']) {
      // We still want to log that we were here.
      $this->consoleLog(dt('Entered @class', ['@class' => get_class($this)]));
      return;
    }

    parent::handle();
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseQuery() {
    $entity_query = parent::getBaseQuery();
    $entity_query->condition('uid', $this->getPreservedUsers(), 'NOT IN');

    return $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPreservedUsers() {
    $preserved_uids = parent::getPreservedUsers();
    // Remove the anonymous user from the list, since we do want to delete that
    // content.
    if (($key = array_search(0, $preserved_uids)) !== FALSE) {
      unset($preserved_uids[$key]);
    }
    return $preserved_uids;
  }

}
