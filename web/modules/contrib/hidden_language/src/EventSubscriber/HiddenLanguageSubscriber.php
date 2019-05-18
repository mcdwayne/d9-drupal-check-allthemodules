<?php

namespace Drupal\hidden_language\EventSubscriber;

use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class HiddenLanguageSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Constructs a new hidden language subscriber.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   */
  public function __construct(AccountInterface $account) {
    $this->account = $account;
  }

  /**
   * Disallow access to hidden language.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   */
  public function onKernelRequestCheckLanguageAccess(GetResponseEvent $event) {
    if ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST) {
      $route_name = RouteMatch::createFromRequest($event->getRequest())->getRouteName();

      // Don't check access if user is trying to log in, register or reset password.
      if ($this->account->isAuthenticated() || !in_array($route_name, array('user.login', 'user.pass', 'user.register'))) {
        $currentLanguage = \Drupal::languageManager()->getCurrentLanguage()->getId();

        // Check if user has access to hidden languages.
        if (!$this->account->hasPermission('access all hidden languages') && !$this->account->hasPermission("access hidden language $currentLanguage")) {
          /** @var ConfigurableLanguage $language */
          $language = ConfigurableLanguage::load($currentLanguage);

          if ($language->getThirdPartySetting('hidden_language', 'hidden', FALSE)) {
            throw new AccessDeniedHttpException();
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onKernelRequestCheckLanguageAccess'];
    return $events;
  }

}
