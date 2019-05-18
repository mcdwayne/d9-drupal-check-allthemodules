<?php

namespace Drupal\okta_saml_login\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Routing\RouteMatch;

/**
 * Redirect 403 to User Login event subscriber.
 *
 * Code borrowed from by Drupal\user\EventSubscriber\AccessDeniedSubscriber.
 */
class AccessDeniedRedirect implements EventSubscriberInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs a new Okta Redirect.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              AccountInterface $current_user,
                              RedirectDestinationInterface $redirect_destination) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * Redirects on 403 Access Denied kernel exceptions.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the access is denied and redirects to user login page.
   */
  public function onKernelException(GetResponseEvent $event) {
    $exception = $event->getException();
    if (!($exception instanceof AccessDeniedHttpException)) {
      return;
    }
    $route_name = RouteMatch::createFromRequest($event->getRequest())->getRouteName();

    // TODO Get this info from a config entity.
    $options = [];
    $options['query'] = $this->redirectDestination->getAsArray();
    $options['absolute'] = TRUE;

    // If user is anonymous and we've got to this point,
    // we want to redirect them to the okta login page.
    if ($this->currentUser->isAnonymous()) {
      // Custom okta login page.
      $loginRoute = 'okta_saml_login.signin_widget';

      // Forward the user to the page they were
      // visiting before they had to log in.
      // Basically, a return to url.
      $requestUrl = \Drupal::service('path.current')->getPath();
      $redirectUrl = Url::fromUserInput($requestUrl)->toString();

      // Construct a redirect response to send the user to login URL.
      $options['query'] = ['ReturnTo' => $redirectUrl];
      $url = Url::fromRoute($loginRoute, [], $options)->toString();

      $response = new RedirectResponse($url, 302);
      $event->setResponse($response);
    }
    elseif ($this->currentUser->isAuthenticated()) {
      switch ($route_name) {
        case 'okta_saml_login.signin_widget';
          // Redirect an authenticated user to the profile page.
          $url = Url::fromRoute(
            'entity.user.canonical',
            ['user' => $this->currentUser->id()],
            $options
          )->toString();

          $response = new RedirectResponse($url, 302);
          $event->setResponse($response);
          break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = ['onKernelException'];
    return $events;
  }

}
