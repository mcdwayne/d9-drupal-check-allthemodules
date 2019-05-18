<?php

namespace Drupal\message_thread\Plugin\Breadcrumbs;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Drupal\message_thread\Entity\MessageThread;

/**
 * MessageThreadBreadcrumbs class.
 */
class MessageThreadBreadcrumbs implements BreadcrumbBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $attributes) {
    $parameters = $attributes->getParameters()->all();

    if (!empty($parameters['message_thread'])) {
      return TRUE;
    }
    if (!empty($parameters['message'])) {
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $parameters = $route_match->getParameters()->all();
    $user = \Drupal::currentUser();
    if (!empty($parameters['message_thread']) && is_object($parameters['message_thread'])) {
      $message_thread = $parameters['message_thread'];

      // Get the parent messages link.
      // We use the current user as a reference
      // since user can only view own messages.
      $thread_template = $message_thread->getTemplate();

      $breadcrumb->addLink(Link::createFromRoute(
        'Messages',
        'message_thread.' . $thread_template->id(),
        ['user' => $user->id()]
      ));

    }
    if (!empty($parameters['message'])) {

      $message = $parameters['message'];
      if ($message->bundle()) {
        $thread_id = message_thread_relationship($message->id());
        // The message may not be associated with a thread.
        if ($thread_id) {
          $message_thread = MessageThread::load($thread_id);
          $thread_template = $message_thread->getTemplate();

          $breadcrumb->addLink(Link::createFromRoute(
            $thread_template->label(),
            'message_thread.' . $thread_template->id(),
            ['user' => $user->id()]
          ));

          $breadcrumb->addLink(Link::createFromRoute(
            $message_thread->get('field_thread_title')->getValue()[0]['value'],
            'entity.message_thread.canonical', [
              'message_thread' => $message_thread->id(),
            ]
          ));

        }
      }
    }

    $contexts = [
      'url',
    ];

    $breadcrumb->addCacheContexts($contexts);
    return $breadcrumb;
  }

}
