<?php

namespace Drupal\login_notification\Subscriber;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\login_notification\LoginNotificationManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Define the notification event subscriber.
 */
class LoginNotificationEventSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * @var \Drupal\login_notification\LoginNotificationManager
   */
  protected $loginNotificationManager;

  /**
   * Login notification event subscriber constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   * @param \Drupal\login_notification\LoginNotificationManagerInterface $login_notification_manager
   */
  public function __construct(
    AccountProxyInterface $account,
    LoginNotificationManagerInterface $login_notification_manager
  ) {
    $this->account = $account->getAccount();
    $this->loginNotificationManager = $login_notification_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['onRequest']
    ];
  }

  /**
   * React on the kernel request.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function onRequest(GetResponseEvent $event) {
    $account = $this->account;
    if ($account->isAnonymous()
      || !$this->accountHasLoggedInRecently()) {
      return;
    }
    $this
      ->loginNotificationManager
      ->invokeLoginNotifications($account);
  }

  /**
   * Account has logged in recently.
   *
   * @param string $interval
   *   A date interval.
   *
   * @return bool
   * @throws \Exception
   */
  protected function accountHasLoggedInRecently($interval = 'PT10S') {
    $user_login = (new \DateTime())
      ->setTimestamp($this->account->login)
      ->add(new \DateInterval($interval));

    return (boolean) (new \DateTime('now') <= $user_login);
  }
}
