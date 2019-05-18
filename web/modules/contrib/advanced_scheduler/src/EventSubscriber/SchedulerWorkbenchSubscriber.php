<?php

namespace Drupal\advanced_scheduler\EventSubscriber;

use Drupal\advanced_scheduler\Controller\SchedulerModeration;
use Drupal\scheduler\SchedulerEvent;
use Drupal\scheduler\SchedulerEvents;
use Drupal\workbench_moderation\Event\WorkbenchModerationEvents;
use Drupal\workbench_moderation\Event\WorkbenchModerationTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class SchedulerWorkbenchIntegrationSubscriber.
 *
 * Scheduler is not able to publish entities for moderation states.
 * that's why we need to develop this class.
 */
class SchedulerWorkbenchSubscriber implements EventSubscriberInterface {

  /**
   * Defined callback for events.
   *
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    // PRE_PUBLISH event
    // Set callback for this event.
    $events[SchedulerEvents::PRE_PUBLISH] = ['onPrePublish', 800];

    // PRE_UNPUBLISH event
    // Set callback for this event.
    $events[SchedulerEvents::PRE_UNPUBLISH] = ['onPreUnpublish', 800];

    // STATE_TRANSITION event which is fired when workbench_moderation's
    // state changes.
    $events[WorkbenchModerationEvents::STATE_TRANSITION] = [
      'onStateTransition',
      800,
    ];

    return $events;

  }

  /**
   * PRE_PUBLISH event is fired by scheduler module.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   - The event defined by the scheduler module.
   */
  public function onPrePublish(SchedulerEvent $event) {
    // SchedulerEvent has a getNode method which gets the node it is acting on.
    $node = $event->getNode();
    // Get node moderation state.
    if (!empty($node->get('moderation_state')->getValue())) {
      $node_moderation_state = $node->get('moderation_state')
        ->getValue()[0]['target_id'];
      // Get all state transition which are configured in content scheduler.
      $transition_config = SchedulerModeration::getScheduledConfig();
      // Check node moderation state existance in transition configuration.
      if (!empty($transition_config) && in_array($node_moderation_state, $transition_config)) {
        // Set the node moderation state to published.
        $node->set('moderation_state', 'published');
        $node->set('status', 1);
        // Save event after update above status and moderation state.
        $event->setNode($node);
      }
    }
  }

  /**
   * PRE_UNPUBLISH event is fired by scheduler module.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   - The event defined by the scheduler module.
   */
  public function onPreUnpublish(SchedulerEvent $event) {
    // SchedulerEvent has a getNode method which gets the node it is acting on.
    $node = $event->getNode();
    // Set the node moderation state to published.
    $node->set('moderation_state', 'archived');
    $node->set('status', 0);
    // Save event after update above status and moderation state.
    $event->setNode($node);

  }

  /**
   * STATE_TRANSITION event is fired by the workbench_moderation module.
   *
   * On a cron run, this event fires after PRE_PUBLISH,
   * So will overwrite any changes you made to the node in that callback.
   *
   * @param \Drupal\workbench_moderation\Event\WorkbenchModerationTransitionEvent $event
   *   - The event defined by the workbench_moderation module.
   */
  public function onStateTransition(WorkbenchModerationTransitionEvent $event) {

    $node = $event->getEntity();
    // If the node is not null
    // and node has a field publish_on.
    if (isset($node) && $node->hasField('publish_on')) {
      $publish_on = $node->get('publish_on')->getValue();
      // Condition for publish_on field is not null
      // and the after state is published.
      if (isset($publish_on[0]['value']) && $publish_on[0]['value'] !== NULL
        && $event->getStateAfter() === 'published') {
        // Set publish date null.
        $node->set('publish_on', NULL);
      }
    }
  }

}
