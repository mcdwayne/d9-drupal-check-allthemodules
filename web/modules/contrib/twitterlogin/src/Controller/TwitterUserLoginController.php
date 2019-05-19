<?php

namespace Drupal\twitterlogin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\twitterlogin\Plugin\Network\TwitterOAuth;
use Drupal\user\Entity\User;

/**
 * TwitterUserLoginController class.
 */
class TwitterUserLoginController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function twitter_user_login() {
    $this->checkUserExist();
    return $this->redirect('<front>');

  }

  /**
   * {@inheritdoc}
   */
  public function checkUserExist() {
    $token = $_SESSION['token'];
    $token_secret = $_SESSION['token_secret'];
    $config = \Drupal::config('twitterlogin.settings');
    $consumerKey = $config->get('consumer_key');
    $consumerSecret = $config->get('consumer_secret');

    // To get the Access Token.
    TwitterOAuth::accessTokenTwitter($consumerKey, $consumerSecret, $token, $token_secret);
    $request_vars = $_SESSION['request_vars'];
    $request_token = $_REQUEST['oauth_token'];
    $user_name = $request_vars['screen_name'];
    
    if (!empty($request_token) && $token == $request_token) {
      $userInfo = user_load_by_name($user_name); 
      if (empty($userInfo)) {
        $userCreation = User::create(array(
          'name'=> $user_name,
          'mail'=> $user_name . '@gmail.com',
          'password'=> FALSE,
          'status' => 1
          ));
        $userCreation->save();
        $userInfo = user_load_by_name($user_name);
        user_login_finalize($userInfo);
      }
      else {
        user_login_finalize($userInfo);
      }
    }
    else {
      drupal_set_message($this->t('Please Provide Valid Twitter OAUTH API'), 'error');
    }
  }

}
