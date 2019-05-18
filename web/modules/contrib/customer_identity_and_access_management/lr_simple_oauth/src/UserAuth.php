<?php

namespace Drupal\lr_simple_oauth;

use Drupal\user\UserAuthInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormStateInterface;
use \LoginRadiusSDK\Utility\Functions;
use \LoginRadiusSDK\LoginRadiusException;
use \LoginRadiusSDK\Clients\IHttpClient;
use \LoginRadiusSDK\Clients\DefaultHttpClient;
use \LoginRadiusSDK\CustomerRegistration\Authentication\UserAPI;

/**
 * Validates user authentication credentials via LoginRadius.
 */
class UserAuth implements UserAuthInterface {

  /**
   * The LoginRadius CIAM user manager.
   *
   * @var \Drupal\lr_ciam\CiamUserManager
   */
  public $module_config;
  protected $apiKey;
  protected $apiSecret;

  /**
   * Constructs a UserAuth object.
   */
  public function __construct() {
    $this->module_config = \Drupal::config('lr_ciam.settings');
    $this->apiKey = trim($this->module_config->get('api_key'));
    $this->apiSecret = trim($this->module_config->get('api_secret'));
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate($username, $password) {
  //  Authenticate the user with the LoginRadius service.  
    $data = '{
    "email": "' . $username . '",
    "password": "' . $password . '",
    "securityanswer": ""
    }';

    // Get a user profile using email and password.
    try {
      $userObj = new UserAPI($this->apiKey, $this->apiSecret, array('output_format' => 'json'));
      $result = $userObj->authLoginByEmail($data);  
    }
    catch (LoginRadiusException $e) {
      return FALSE;
    }

    // Check if the user was authenticated with LoginRadius service.
    if (isset($result->access_token) && $result->access_token != '') {
      // Get uid from db using email.
      $query = \Drupal::database()->select('users_field_data', 'u');
      $query->addField('u', 'uid');
      $query->condition('u.mail', $result->Profile->Email[0]->Value);
      $uid = $query->execute()->fetchField();

      // If User exist on LoginRadius but does not exist on Drupal then create user on Drupal.
      if (isset($uid) && $uid == '') {
        $fields = array(
          'name' => $username,
          'mail' => $result->Profile->Email[0]->Value,
          'init' => $result->Profile->Email[0]->Value,
          'pass' => $password,
          'status' => '1',
        );
        $new_user = User::create($fields);
        $new_user->save();
        return $new_user->id();
      }
      else {
        return $uid;
      }
    }
    else {
      return FALSE;
    }
  }
}
