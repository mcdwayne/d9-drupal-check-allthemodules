<?php

namespace Drupal\enforce_user_fields\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class EnforceUserFieldsSubscriber.
 */
class EnforceUserFieldsSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private $routeMatch;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  private $messenger;

  /**
   * EnforceUserFieldsSubscriber constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *  The current user.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *  The route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *  The request stack.
   * @param \Drupal\Core\Messenger\MessengerInterface
   *  The messenger.
   */
  public function __construct(AccountProxyInterface $current_user, RouteMatchInterface $route_match, RequestStack $request_stack, MessengerInterface $messenger) {
    $this->currentUser = $current_user;
    $this->routeMatch = $route_match;
    $this->requestStack = $request_stack;
    $this->messenger = $messenger;
  }

  public function checkForUserFields(GetResponseEvent $event) {
    $route_name = $this->routeMatch->getRouteName();
    if ($this->currentUser->isAnonymous() || enforce_user_fields_user_has_admin_role($this->currentUser) || in_array($route_name, [
      'entity.user.edit_form',
      'user.logout',
      'image.style_public',
      'image.style_private',
    ])) {
      return;
    }
    $is_ajax = $this->requestStack->getCurrentRequest()->isXmlHttpRequest();
    if ($is_ajax) {
      return;
    }
    if (!empty($_SESSION['enforce_user_fields'])) {
      $this->messenger->addMessage(t('Fill out the required fields to complete your profile.'), MessengerInterface::TYPE_ERROR);
      $event->setResponse(new RedirectResponse(Url::fromRoute('entity.user.edit_form', [
        'user' => $this->currentUser->id(),
        'destination' => Url::fromRouteMatch($this->routeMatch)->toString(),
      ])->toString()));
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('checkForUserFields');
    return $events;
  }


}
