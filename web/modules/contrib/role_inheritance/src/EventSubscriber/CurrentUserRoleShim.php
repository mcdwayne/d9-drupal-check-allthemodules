<?php

namespace Drupal\role_inheritance\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Session\UserSession;

/**
 * Set extra roles on the object provided by \Drupal::currentUser().
 *
 * Due to the issue described in https://www.drupal.org/node/2345611, the
 * object returned by the \Drupal::currentUser() function is not an instance of
 * the User entity and is not handled by the orm. Thus, role_inheritance cannot
 * propertly modify the list of active role for the currently signed in user.
 * This event listener provides the required shim such that the correct roles
 * can be added to the class.
 */
class CurrentUserRoleShim implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Subscribe to the Request Event with Priority 299, so that it runs
    // imediately following the core AuthenticationSubscriber.
    // @see Drupal\Core\EventSubscriber\AuthenticationSubscriber
    return([
      KernelEvents::REQUEST => [
        ['alterUser', 299],
      ],
    ]);
  }

  /**
   * Replace the current UserSession object with one that has extended roles.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Event object being handled.
   */
  public function alterUser(GetResponseEvent $event) {
    $request = $event->getRequest();
    $proxy = \Drupal::currentUser();
    $user = $proxy->getAccount();

    if ($user instanceof UserSession) {
      // Replace the current account with.
      $user = new UserSession([
        "uid"                       => $user->id(),
        "mail"                      => $user->getEmail(),
        "name"                      => $user->getAccountName(),
        "roles"                     => _role_inheritance_extendroles($user->getRoles()),
        "access"                    => $user->getLastAccessedTime(),
        "timezone"                  => $user->getTimeZone(),
        "preferred_langcode"        => $user->getPreferredLangcode(),
        "preferred_admin_langcode"  => $user->getPreferredAdminLangcode(),
      ]);
      $proxy->setAccount($user);
    }
  }

}
