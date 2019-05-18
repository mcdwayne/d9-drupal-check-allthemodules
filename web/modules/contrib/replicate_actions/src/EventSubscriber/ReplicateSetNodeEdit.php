<?php

namespace Drupal\replicate_actions\EventSubscriber;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\replicate\Events\ReplicateAlterEvent;
use Drupal\replicate\Events\AfterSaveEvent;
use Drupal\replicate\Events\ReplicatorEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Makes replicated nodes unpublished and redirect to edit mode.
 */
class ReplicateSetNodeEdit implements EventSubscriberInterface {

  /**
   * Sets the status of a replicated node to unpublished.
   *
   * @param \Drupal\replicate\Events\ReplicateAlterEvent $event
   *   The event fired by the replicator.
   */
  public function setUnpublished(ReplicateAlterEvent $event) {
    $cloned_entity = $event->getEntity();

    if (!$cloned_entity instanceof Node) {
      return;
    }

    $cloned_entity->set('status', Node::NOT_PUBLISHED);
  }

  /**
   * Make a redirect to "edit" mode.
   *
   * @param \Drupal\replicate\Events\AfterSaveEvent $event
   *   The event fired by the replicator.
   */
  public function makeRedirect(AfterSaveEvent $event) {
    $cloned_entity = $event->getEntity();

    if (!$cloned_entity instanceof Node) {
      return;
    }

    $edit_form_url = Url::fromRoute('entity.node.edit_form', [
      'node' => $cloned_entity->id(),
    ]);
    $response = new RedirectResponse($edit_form_url->toString());
    $response->send();

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[ReplicatorEvents::REPLICATE_ALTER][] = 'setUnpublished';
    $events[ReplicatorEvents::AFTER_SAVE][] = 'makeRedirect';

    return $events;
  }

}
