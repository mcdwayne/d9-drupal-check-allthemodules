<?php

namespace Drupal\dbee\Authentication\Provider;

use Drupal\user\Authentication\Provider\Cookie;
use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\UserSession;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Cookie based authentication provider.
 */
class DbeeCookie extends Cookie implements AuthenticationProviderInterface {

  /**
   * Returns the UserSession object for the given session.
   *
   * Override the cookie provider Drupal\user\Authentication\Provider\Cookie,
   * simply decrypting email and init for the current user.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   *
   * @return \Drupal\Core\Session\AccountInterface|null
   *   The UserSession object for the current user, or NULL if this is an
   *   anonymous session.
   */
  protected function getUserFromSession(SessionInterface $session) {
    if ($uid = $session->get('uid')) {
      // @todo Load the User entity in SessionHandler so we don't need queries.
      // @see https://www.drupal.org/node/2345611
      $values = $this->connection
        ->query('SELECT * FROM {users_field_data} u WHERE u.uid = :uid AND u.default_langcode = 1', [':uid' => $uid])
        ->fetchAssoc();

      // Check if the user data was found and the user is active.
      if (!empty($values) && $values['status'] == 1) {
        // Add the user's roles.
        $rids = $this->connection
          ->query('SELECT roles_target_id FROM {user__roles} WHERE entity_id = :uid', [':uid' => $values['uid']])
          ->fetchCol();
        $values['roles'] = array_merge([AccountInterface::AUTHENTICATED_ROLE], $rids);

        // Here : decrypt emails!
        if (!empty($values['mail']) || !empty($values['init'])) {
          $decrypt = dbee_unstore($values);
          foreach (['mail', 'init'] as $dbee_field) {
            if (isset($decrypt[$dbee_field])) {
              $values[$dbee_field] = $decrypt[$dbee_field];
            }
          }
        }
        return new UserSession($values);
      }
    }

    // This is an anonymous session.
    return NULL;
  }

}
