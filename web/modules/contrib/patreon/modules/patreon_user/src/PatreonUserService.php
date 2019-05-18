<?php

namespace Drupal\patreon_user;

use Drupal\patreon\PatreonService;
use Drupal\user\UserInterface;
use Drupal\Core\Url;
use Drupal\patreon\PatreonGeneralException;
use Drupal\user\Entity\User;
use Drupal\Component\Utility\Xss;
use \Drupal\user\Entity\Role;

/**
 * Class PatreonUserService.
 *
 * @package Drupal\patreon_user
 */
class PatreonUserService extends PatreonService {

  /**
   * {@inheritdoc}
   */
  public function getCallback() {
    return Url::fromRoute('patreon_user.patreon_user_controller_oauth', array(), array('absolute' => TRUE));
  }

  /**
   * {@inheritdoc}
   */
  public function storeTokens($tokens, UserInterface $account = NULL) {
    if (!$account) {
      $account = \Drupal::currentUser();
    }

    if ($account->id() > 0) {
      $account->set('user_patreon_token', $tokens['access_token']);
      $account->set('user_patreon_refresh_token', $tokens['refresh_token']);
      $account->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getStoredTokens(UserInterface $account = NULL) {
    $return = array();

    if (!$account) {
      $account = \Drupal::currentUser();
    }

    if ($account->id() > 0) {
      $return['refresh_token'] = $account->get('user_patreon_refresh_token');
      $return['access_token'] = $account->get('user_patreon_token');
    }

    return $return;
  }

  /**
   * Helper to check whether current Patreon user is allowed to log in.
   *
   * @param \Art4\JsonApiClient\Document $user_return
   *   Results from patreon_fetch_user().
   *
   * @return bool
   *   TRUE if user meets current Patreon settings restrictions on log in.
   */
  public function canLogin(\Art4\JsonApiClient\Document $user_return) {
    $return = FALSE;

    if ($patreon_id = $this->bridge->getValueByKey($user_return, 'data.id')) {
      $config = \Drupal::config('patreon_user.settings');
      if ($settings = $config->get('patreon_user_registration')) {
        if ($settings != PATREON_USER_NO_LOGIN) {
          if ($settings == PATREON_USER_ONLY_PATRONS) {
            if ($this->isPatron($user_return)) {
              $return = TRUE;
            }
          }
          else {
            $return = TRUE;
          }
        }
      }
    }

    return $return;
  }

  /**
   * Helper to fetch an existing user or create a new one from Patreon account.
   *
   * @param \Patreon\JSONAPI\ResourceItem $patreon_user
   *   Results from patreon_fetch_user().
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface
   *   A Drupal user object, or FALSE on error.
   *
   * @throws \Drupal\patreon_user\PatreonUserUserException
   */
  public function getUser($patreon_user) {
    $return = FALSE;

    if ($patreon_id = $this->bridge->getValueByKey($patreon_user, 'id')) {
      try {
        if ($account = $this->getUserFromId($patreon_id)) {
          $return = $account;
        }
        else {
          try {
            $return = $this->createUserFromReturn($patreon_user);
          }
          catch (\Exception $e) {

            // Pass the Exception up to the next level.
            throw new PatreonUserUserException($e->getMessage());
          }
        }
      }
      catch (\Exception $e) {

        // Pass the Exception up to the next level.
        throw new PatreonUserUserException($e->getMessage());
      }
    }

    return $return;
  }

  /**
   * Returns a Drupal user account linked to a Patreon account id.
   *
   * @param int $patreon_id
   *   A valid patreon account id.
   *
   * @return bool|\Drupal\user\Entity\User
   *   A Drupal user account, or FALSE if not found.
   *
   * @throws \Drupal\patreon\PatreonGeneralException
   *   Errors if more than one user linked to the account.
   */
  public function getUserFromId($patreon_id) {
    $return = FALSE;
    $query = \Drupal::entityQuery('user')
      ->condition('user_patreon_id', $patreon_id);

    $result = $query->execute();

    if (isset($result)) {
      if (count($result) > 1) {
        throw new PatreonGeneralException($this->t('Multiple users linked to the Patreon account :id', array(':id' => $patreon_id)));
      }
      elseif ($account = User::load(key($result))) {
        if ($account->id() == 1) {
          \Drupal::logger('patreon_user')->error($this->t('Patreon user :id linked to User 1. This could cause security issues.', array(':id' => $patreon_id)));
        }
        $return = $account;
      }
    }

    return $return;
  }

  /**
   * Creates a Drupal user account from Patreon API data.
   *
   * @param \Patreon\JSONAPI\ResourceItem $data
   *   Results from patreon_fetch_user().
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface
   *   A Drupal user object, or FALSE on error.
   *
   * @throws \Drupal\patreon_user\PatreonUserUserException
   */
  public function createUserFromReturn(\Patreon\JSONAPI\ResourceItem $data) {
    $return = NULL;
    if ($patreon_id = $this->bridge->getValueByKey($data, 'id')) {
      $return = User::create();
      $return->setPassword(user_password(20));
      $return->enforceIsNew();

      // We need an email address, or we can't continue.
      if ($mail = $this->bridge->getValueByKey($data, 'attributes.email')) {

        // If the user mail exists, it must be the same user.
        if ($existing_mail = user_load_by_mail($mail)) {
          $return = $existing_mail;
          unset($existing_mail);
        }
        else {
          $return->setEmail($mail);
        }

        // But if the name exists, it could be someone else.
        if ($name = Xss::filter($this->bridge->getValueByKey($data, 'attributes.full_name'))) {
          $name = $this->bridge->getUniqueUserName($name, $patreon_id);
          $return->setUsername($name);
          $alter = array('#user' => &$return, '#patreon_data' => $data);

          // Allow other modules to add field data.
          \Drupal::moduleHandler()->alter('patreon_user_create_user', $alter);

          // Add the Patreon ID.
          $return->set('user_patreon_id', Xss::filter($patreon_id));
          $this->assignRoles($return, $data);
        }
      }
      else {
        throw new PatreonUserUserException($this->t('No Patreon Email address in provided data array.'));
      }
    }
    else {
      throw new PatreonUserUserException($this->t('No Patreon ID in provided data array.'));
    }

    if ($return) {
      try {
        $return->save();
      }
      catch (\Exception $e) {
        throw new PatreonUserUserException($e->getMessage());
      }
    }
    else {
      throw new PatreonUserUserException($this->t('Error creating user.'));
    }

    return $return;
  }

  /**
   * Assign the patreon user or deleted patreon user roles based on status.
   *
   * @param object $account
   *   A Drupal user account to update.
   * @param \Patreon\JSONAPI\ResourceItem $patreon_user
   *   Results from patreon_fetch_user().
   */
  public function assignRoles(&$account, \Patreon\JSONAPI\ResourceItem $patreon_user) {
    $config = \Drupal::config('patreon_user.settings');
    $deleted = $this->bridge->isDeletedUser($patreon_user, $account->getAccountName());
    $patreon_user_roles = $config->get('patreon_user_roles');

    if (!$patreon_user_roles) {
      $this->createRoles();
      $patreon_user_roles = $config->get('patreon_user_roles');
    }

    if ($deleted) {
      $account->addRole('deleted_patreon_user');
    }
    else {
      $account->addRole('patreon_user');
    }

    if ($patreon_data = $this->fetchUser()) {
      foreach ($this->bridge->getPatronPledges as $pledge) {
        if ($data = $this->bridge->getValueByKey($pledge, 'data')) {
          if ($id = $this->bridge->getValueByKey($data, 'id')) {
            if (array_key_exists($id, $patreon_user_roles)) {
              $account->addRole($patreon_user_roles[$id]);
            }
          }
        }
      }
    }
  }

  /**
   * Helper to create Drupal roles from Patreon reward types.
   */
  public function createRoles() {
    $service = \Drupal::service('patreon.api');
    $tokens = $service->getStoredTokens();

    if (array_key_exists('access_token', $tokens)) {
      $service->bridge->setToken($tokens['access_token']);
      $campaigns = $service->fetchCampaign();
      $roles = $this->bridge->getPatreonRoleNames($campaigns);
      $config_data = array();
      $all = user_role_names();

      foreach ($roles as $label => $patreon_id) {
        $id = strtolower(str_replace(' ', '_', $label));
        if (!in_array($label, $all)) {
          $data = array(
            'id' => $id,
            'label' => $label,
          );

          $role = Role::create($data);
          $role->save();
        }

        $key = ($patreon_id) ? $patreon_id : $id;
        $config_data[$key] = $id;
      }

      $config = \Drupal::service('config.factory')
        ->getEditable('patreon_user.settings');
      $config->set('patreon_user_roles', $config_data)->save();
    }
  }

}
