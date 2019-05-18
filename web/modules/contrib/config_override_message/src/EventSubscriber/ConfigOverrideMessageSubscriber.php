<?php

namespace Drupal\config_override_message\EventSubscriber;

use Drupal\config_override_message\ConfigOverrideMessageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Config override message subscriber for controller requests.
 */
class ConfigOverrideMessageSubscriber implements EventSubscriberInterface {

  /**
   * The config override message manager.
   *
   * @var \Drupal\config_override_message\ConfigOverrideMessageManagerInterface
   */
  protected $manager;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The route admin context to determine whether a route is an admin one.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * Constructs a ConfigOverrideMessageSubscriber object.
   *
   * @param \Drupal\config_override_message\ConfigOverrideMessageManagerInterface $config_override_message_manager
   *   The config override message manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The route admin context service.
   */
  public function __construct(ConfigOverrideMessageManagerInterface $config_override_message_manager, RouteMatchInterface $route_match, AccountInterface $current_user, MessengerInterface $messenger, AdminContext $admin_context) {
    $this->manager = $config_override_message_manager;
    $this->routeMatch = $route_match;
    $this->currentUser = $current_user;
    $this->messenger = $messenger;
    $this->adminContext = $admin_context;
  }

  /**
   * Check to see if a config override message should be displayed.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
      return;
    }

    // Check that the current user can view config override message.
    if (!$this->currentUser->hasPermission('view config override message')) {
      return;
    }

    // Only display message on admin routes.
    if (!$this->adminContext->isAdminRoute($this->routeMatch->getRouteObject())) {
      return;
    }

    // Display config override messages for the current path.
    $path = $event->getRequest()->getPathInfo();
    $path = str_replace(base_path(), '/', $path);
    $messages = $this->manager->getMessages();
    if (isset($messages[$path])) {
      foreach ($messages[$path] as $message) {
        $this->messenger->addWarning($message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[KernelEvents::REQUEST][] = ['onKernelRequest'];
    return $events;
  }

}
