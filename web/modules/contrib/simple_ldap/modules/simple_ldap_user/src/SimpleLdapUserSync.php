<?php

namespace Drupal\simple_ldap_user;


use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_ldap\SimpleLdapServer;
use Drupal\simple_ldap_user\Events\Events;
use Drupal\simple_ldap_user\Events\SimpleLdapUserEvent;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SimpleLdapUserSync {

  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * @var \Drupal\simple_ldap\SimpleLdapServer
   */
  protected $server;

  /**
   * @var \Drupal\simple_ldap_user\SimpleLdapUserManager
   */
  protected $ldapManager;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected $password;

  /**
   * SimpleLdapUserSync constructor.
   *
   * @param \Drupal\simple_ldap\SimpleLdapServer $server
   * @param \Drupal\simple_ldap_user\SimpleLdapUserManager $ldap_manager
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   * @param \Drupal\Core\Password\PasswordInterface $password
   */
  public function __construct(
    SimpleLdapServer $server,
    SimpleLdapUserManager $ldap_manager,
    EventDispatcherInterface $event_dispatcher,
    PasswordInterface $password
  ) {
    $this->server = $server;
    $this->ldapManager = $ldap_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->password = $password;
  }

  /**
   * Imports the LDAP information into Drupal if necessary.
   *
   * @param \Drupal\simple_ldap_user\SimpleLdapUser $user
   *   The LDAP user.
   * @param string|null $password
   *   The password.
   *
   * @return bool|\Drupal\user\UserInterface
   *   The Drupal user.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function importIntoDrupal(SimpleLdapUser $user, $password = NULL) {
    $needs_saving = FALSE;
    $account = $this->ldapManager->loadDrupalUser($user);
    if ($account instanceof UserInterface) {
      $same_password = $this->password
        ->check($password, $account->getPassword());
      if (!$same_password) {
        $account->setPassword($password);
        $needs_saving = TRUE;
      }
    }
    else {
      $account = $this->ldapManager->createDrupalUser($user, $password);
      $this->messenger->addStatus($this->t(
        'New user created for %name.',
        ['%name' => $account->getAccountName()]
      ));
    }
    $this->updateDrupalUser($user, $account, $needs_saving);
    return $account;
  }

  /**
   * Dispatches an event so other modules can map LDAP properties.
   *
   * Once all the subscribers have been processed, we determine if the Drupal
   * user object changed. If that's the case we slate the user for saving, once.
   *
   * @param \Drupal\simple_ldap_user\SimpleLdapUser $user
   *   The LDAP properties for this user.
   * @param \Drupal\user\UserInterface $account
   *   The Drupal account object.
   * @param bool $force_save
   *   Set to TRUE
   *
   */
  protected function updateDrupalUser(SimpleLdapUser $user, UserInterface $account, $force_save = FALSE) {
    static $scheduled_saves = [];
    $uuid = $account->uuid();
    $save_happening = $force_save || !empty($scheduled_saves[$uuid]);
    // Fire the synchronization event so other modules can map properties
    // as needed.
    $event = new SimpleLdapUserEvent($user, $account);
    // If save is enforced we can safely skip serialization.
    $hashed_pre = $save_happening ? '' : $this->serialize($account);
    $this->eventDispatcher->dispatch(Events::USER_SYNCHRONIZATION, $event);
    $hashed_post = $save_happening ? '' : $this->serialize($account);
    // The serialization component is optional, if the serializer is not present
    // we have to assume we need to save the entity. That is because we cannot
    // check for changes caused by event subscribers.
    $has_changed = $hashed_pre !== $hashed_post;
    if (empty($scheduled_saves[$uuid]) && ($has_changed || $force_save)) {
      // Schedule saving til the end of the request. Only save once even if the
      // event is dispatched multiple times.
      drupal_register_shutdown_function([$account, 'save']);
      $scheduled_saves[$uuid] = TRUE;
    }
  }

  /**
   * Serializes the account object. Used to see if the account changed.
   *
   * @param \Drupal\user\UserInterface $account
   *   The account.
   *
   * @return string
   *   The serialized account.
   */
  protected function serialize(UserInterface $account) {
    return md5(serialize($account));
  }
}
