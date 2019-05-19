<?php

namespace Drupal\simple_ldap_user;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\simple_ldap\SimpleLdapException;
use Drupal\simple_ldap\SimpleLdapServer;
use Drupal\user\UserInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageException;

/**
 * Manages the loading and syncing of data between LDAP server and Drupal.
 */
class SimpleLdapUserManager {

  /**
   * @var UserInterface
   */
  protected $user;

  /**
   * @var SimpleLdapServer
   */
  protected $server;

  /**
   * @var ImmutableConfig
   */
  protected $config;

  /**
   * @var QueryFactory
   */
  protected $query;

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entity_manager;

  /**
   * @var array
   */
  protected $cache = [];

  public function __construct(SimpleLdapServer $server, ConfigFactoryInterface $config_factory, QueryFactory $query, EntityTypeManagerInterface $entity_manager) {
    $this->server = $server;
    $this->config = $config_factory->get('simple_ldap.user');
    $this->query = $query;
    $this->entity_manager = $entity_manager;
  }

  /**
   * Checks if a user exists on the LDAP server with a certain name.
   *
   * It first checks using the name attribute, and then the email attribute.
   *
   * @param string $name
   *  The name to search for on the server.
   *
   * @return mixed
   *  A SimpleLdapUser object if the user exists on the server, FALSE if
   *   otherwise.
   *
   * @throws \Drupal\simple_ldap\SimpleLdapException
   */
  public function getLdapUser($name) {
    $cid = sprintf('LdapUser::%s', $name);
    if (array_key_exists($cid, $this->cache)) {
      return $this->cache[$cid];
    }
    $name = $this->cleanName($name);

    $name_attribute = $this->config->get('name_attribute');
    $mail_attribute = $this->config->get('mail_attribute');
    $base_dn = $this->config->get('basedn');
    $scope = $this->config->get('user_scope');

    if (empty($name_attribute) || empty($mail_attribute)) {
      throw new SimpleLdapException('Unable to find valid configuration for LDAP User Drupal module.', NULL);
    }

    $object_classes = $this->config->get('object_class');
    $object_class_filter = '';
    if (isset($object_classes)) {
      $object_class_filter = '(&(objectclass=' . implode(')(objectclass=', $object_classes) . '))';
    }

    $filter_list = array();
    $filter_list[] = '(&(' . $name_attribute . '=' . $name . ')' . $object_class_filter . ')';
    $filter_list[] = '(&(' . $mail_attribute . '=' . $name . ')' . $object_class_filter . ')';

    if (!$this->server->bind()) {
      $this->cache[$cid] = FALSE;
      return FALSE;
    }

    foreach ($filter_list as $filter) {
      try {
        // @TODO get the full attributes to pass into this search
        $results = $this->server->search($base_dn, $filter, $scope, [], 0, 1);
      }
      catch (SimpleLdapException $e) {
        if ($e->getCode() == -1) {
          $results = array();
        }
        else {
          $this->cache[$cid] = FALSE;
          throw $e;
        }
      }

      if (count($results) == 1) {
        $simple_ldap_user = new SimpleLdapUser(key($results), array_shift($results));
        $this->cache[$cid] = $simple_ldap_user;
        return $simple_ldap_user;
      }
    }

    return FALSE;
  }

  /**
   * Load a Drupal user based on an LDAP user.
   *
   * @param SimpleLdapUser $user
   *  The LDAP user to check for.
   *
   * @return bool|UserInterface
   *  Returns a loaded User object if found, FALSE if otherwise.
   */
  public function loadDrupalUser(SimpleLdapUser $user) {
    $uid = $this->userIdFromLdapUser($user);
    if ($uid === FALSE) {
      return FALSE;
    }
    try {
      /** @var \Drupal\user\UserInterface $user */
      $user = $this->entity_manager->getStorage('user')->load($uid);
      return $user;
    }
    catch (PluginException $exception) {
      watchdog_exception('simple_ldap_user', $exception);
    }
    return FALSE;
  }

  /**
   * Extracts the Drupal user ID from the LdapUser.
   *
   * @param \Drupal\simple_ldap_user\SimpleLdapUser $user
   *   The LDAP user object.
   *
   * @return integer|bool
   *   The user ID.
   */
  protected function userIdFromLdapUser(SimpleLdapUser $user) {
    $attribute_values = $user->getAttributes();
    $name_attribute = $this->config->get('name_attribute');
    $mail_attribute = $this->config->get('mail_attribute');
    $cid = sprintf('uids::%s:%s', $name_attribute, $mail_attribute);
    if (array_key_exists($cid, $this->cache)) {
      return $this->cache[$cid];
    }

    $query = $this->query->get('user', 'OR')
      ->condition('name', $attribute_values[$name_attribute][0])
      ->condition('mail', $attribute_values[$mail_attribute][0]);

    $results = $query->execute();
    $uid = reset($results);
    $this->cache[$cid] = $uid;
    return $uid;
  }

  /**
   * Create a corresponding Drupal user based on an LDAP user's attributes.
   *
   * @param SimpleLdapUser $user
   *  The LDAP user to use to create a Drupal user.
   * @param string $password
   *  The password to give the new user.
   *
   * @return boolean|UserInterface
   *  A new user object with name and user populated. FALSE if the user could not be created.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function createDrupalUser(SimpleLdapUser $user, $password = NULL) {
    $attribute_values = $user->getAttributes();
    $name_attribute = $this->config->get('name_attribute');
    $mail_attribute = $this->config->get('mail_attribute');

    /** @var \Drupal\user\UserInterface $new_user */
    $new_user = $this->entity_manager->getStorage('user')->create(array(
        'name' => $attribute_values[$name_attribute][0],
        'mail' => $attribute_values[$mail_attribute][0],
      )
    );

    if ($password) {
      $new_user->setPassword($password);
    }

    $new_user->enforceIsNew();
    $new_user->activate();

    try {
      $new_user->save();
    }
    catch (EntityStorageException $e) {
      return FALSE;
    }


    return $new_user;
  }

  /**
   * Make the name safe for LDAP searches.
   *
   * @param $name
   * @return string
   */
  protected function cleanName($name) {
    return preg_replace(array('/\(/', '/\)/'), array('\\\(', '\\\)'), $name);
  }
}
