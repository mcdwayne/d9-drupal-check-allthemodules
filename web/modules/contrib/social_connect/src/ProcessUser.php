<?php

namespace Drupal\social_connect;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\Entity\User;
use Drupal\externalauth\Authmap;
use Drupal\externalauth\ExternalAuth;
use Drupal\social_connect\ProcessUser;

/**
 * ProcessUser class.
 */
class ProcessUser {

  /**
   * The ExternalAuth.
   *
   * @var \Drupal\externalauth\ExternalAuth
   */
  protected $externalAuth;

  /**
   * The Authmap.
   *
   * @var \Drupal\externalauth\Authmap
   */
  protected $authMap;

  /**
   * Constructs a \Drupal\social_connect\ProcessUser object.
   * 
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\externalauth\Authmap $auth_map
   * The authmap service
   * @param \Drupal\externalauth\ExternalAuth $external_auth
   * The external_auth service
   */
  public function __construct(Authmap $auth_map, ExternalAuth $external_auth) {
    $this->authMap = $auth_map;
    $this->externalAuth = $external_auth;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $auth_map = $container->get('externalauth.authmap');
    $external_auth = $container->get('externalauth.externalauth');
    return new static($auth_map, $external_auth);
  }

  /**
   * Helper function for create new user.
   * @param type $name
   * @param type $email
   * @return boolean
   */
  public function createUser($name, $email) {
    $new_user = [
      'name' => $name,
      'pass' => user_password(),
      'init' => $name,
      'mail' => $email,
      'status' => 1,
      'access' => REQUEST_TIME,
    ];
//    $storage = $this->entityManager()->getStorage('user');
    $storage = \Drupal::entityTypeManager()->getStorage('user');
    $account = $storage->create($new_user);
    $account->save();

    if (!$account->id()) {
      return FALSE;
    }

    // Email notification.
    $configs = \Drupal::configFactory()->get('social_connect.settings');
    $global_settings = $configs->get('global');
    if ($global_settings['mail_notify'] === 1) {
      if ($account->status) {
        _user_mail_notify('register_no_approval_required', $account);
      }
      else {
        _user_mail_notify('register_pending_approval', $account);
      }
    }

    return $account;
  }

  /**
   * Helper function for update user fields.
   * @param type $account
   * @param type $user_info
   * @return boolean
   */
  public function updateUser($account, $user_info) {
    foreach ($user_info as $field_name => $field_value) {
      try {
        $account->set($field_name, $field_value);
      }
      catch (Exception $ex) {
        return FALSE;
      }
    }

// TO DO:
// If module "domain" enabled we will add current domain to current user.
//    if (module_exists('domain')) {
//      $current_domain = domain_get_domain();
//      if (isset($current_domain['domain_id'])) {
//        $domain_id = ($current_domain['domain_id'] == 0) ? "-1" : $current_domain['domain_id'];
//        if (!isset($account->domain_user[$domain_id])) {
//          $edit['domain_user'][$domain_id] = $domain_id;
//        }
//      }
//    }
    try {
      $account->save();
    }
    catch (Exception $ex) {
      return FALSE;
    }

    return $account;
  }

  /**
   * Manage externalauth
   * @param type $source
   */
  public function externalAuthLoginRegister($source, $account) {
    // Check if authmap exist. If not - create it.
    $authmaps = $this->authMap->get($account->id(), 'social_' . $source);
    if (!$authmaps) {
      $this->authMap->save($account, 'social_' . $source, $account->getAccountName());
    }
    // Login or Register user.
    $this->externalAuth->loginRegister($account->getAccountName(), 'social_' . $source);
  }

}
