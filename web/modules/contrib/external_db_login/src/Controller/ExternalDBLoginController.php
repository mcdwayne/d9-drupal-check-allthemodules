<?php

namespace Drupal\external_db_login\Controller;

use Drupal\external_db_login\ExternalDBLoginService;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Password\PhpassHashedPassword;
use Drupal\Core\Language\LanguageManager;

/**
 * ExternalDBLoginController controller.
 */
class ExternalDBLoginController extends ControllerBase {

  protected $externalDBLoginService;

  /**
   * {@inheritdoc}
   */
  public function __construct(ExternalDBLoginService $externalDBLoginService) {
    $this->externalDBLoginService = $externalDBLoginService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('external_db_login.service')
    );
  }

  /**
   * Create new user.
   *
   * @param mixed $username
   *   Pass username.
   * @param mixed $email
   *   Pass email id.
   * @param mixed $password
   *   Pass password.
   */
  public function createNewUser($username, $email, $password) {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $user = User::create();
    // Mandatory.
    $user->setPassword($password);
    $user->enforceIsNew();
    $user->setEmail($email);
    $user->setUsername($username);

    // Optional.
    $user->set('init', 'external_mail');
    $user->set('langcode', $language);
    $user->set('preferred_langcode', $language);
    $user->set('preferred_admin_langcode', $language);
    $user->addRole();
    $user->activate();

    // Save user account.
    $result = $user->save();
    return $result;
  }

  /**
   * Update user.
   *
   * @param mixed $id
   *   Pass user id.
   * @param mixed $password
   *   Pass password.
   */
  public function updateNewUser($id, $password) {
    $user = User::load($id);

    // Set new password.
    $user->setPassword($password);
    // Save user account.
    $result = $user->save();
    return $result;
  }

  /**
   * Check user exist in external database or not.
   *
   * @param mixed $email
   *   Pass email id.
   * @param mixed $password
   *   Pass Password.
   */
  public function checkUser($email, $password) {

    $get_encrypt_type = $this->externalDBLoginService->getConfig('external_db_login_user_password_encypt');
    $password_encrypt = $this->encryptPassword($get_encrypt_type, $password, $email);
    if (!empty($password_encrypt)) {
      $auth_password = $this->externalDBLoginService->authUserAccount($email, $password_encrypt);
      $create_new_user = FALSE;
      if ($password_encrypt === 1 || !empty($auth_password)) {
        $check_user_exist = user_load_by_mail($email);
        if ($check_user_exist) {
          $uid = $check_user_exist->get('uid')->value;
          $create_new_user = $this->updateNewUser($uid, $password);
          $username = $check_user_exist->get('name')->value;
        }
        else {
          $username_array = explode('@', $email);
          $username = $username_array[0];
          $load_user = user_load_by_name($username);
          if ($load_user) {
            $username = $username . '_' . $load_user->get('uid')->value . mt_rand(0, 10000);
          }
          $create_new_user = $this->createNewUser($username, $email, $password);
        }
      }
      if (!empty($create_new_user) && !empty($username)) {
        return $username;
      }
      else {
        return FALSE;
      }
    }
    else {
      $load_user_by_name = user_load_by_name($email);
      $load_user_by_email = user_load_by_mail($email);
      if ($load_user_by_name) {
        return $email;
      }
      elseif ($load_user_by_email) {
        $username = $load_user_by_email->get('name')->value;
        return $username;
      }
      return FALSE;
    }
  }

  /**
   * Encrypt password.
   *
   * @param mixed $encypt_value
   *   Selected encrypt value.
   * @param mixed $password
   *   Pass password.
   * @param mixed $email
   *   Pass email id.
   */
  protected function encryptPassword($encypt_value, $password, $email) {
    // Get user hash value fron service.
    $userhash = $this->externalDBLoginService->getUserPasswordHash($email);
    if (empty($userhash)) {
      return FALSE;
    }
    switch ($encypt_value) {
      case 'md5':
        $new_password = md5($password);
        break;

      case 'sha1':
        $new_password = sha1($password);
        break;

      case 'sha512':
        $new_password = $this->externalCheckPassword($password, $userhash);
        break;

      case 'hash':
        $new_password = $this->externalCheckPassword($password, $userhash);
        break;

      case 'phpass':
        $new_password = $this->externalCheckPassword($password, $userhash);
        break;
    }
    return $new_password;
  }

  /**
   * Check Password.
   *
   * @param mixed $password
   *   Pass password.
   * @param mixed $hash
   *   Pass user's hash value.
   * @param mixed $user_id
   *   Pass user id.
   */
  protected function externalCheckPassword($password, $hash, $user_id = '') {

    // If the hash is still md5.
    if (strlen($hash) <= 32) {
      $hashpassword_obj = new PhpassHashedPassword();
      $check = ($hash == md5($password));
      if ($check && $user_id) {
        // Rehash using new hash.
        $hash = $hashpassword_obj->hash($password);
      }
      return $hashpassword_obj->check($password, $hash);
    }

    // If the stored hash is longer than an MD5, presume the
    // new style phpass portable hash.
    $hashpassword_obj = new PhpassHashedPassword();

    return (int) $hashpassword_obj->check($password, $hash);
  }

}
