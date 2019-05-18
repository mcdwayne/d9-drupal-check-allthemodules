<?php
namespace Drupal\linkedinlogin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;

use Drupal\linkedinlogin\Plugin\Network\HttpClass;
use Drupal\linkedinlogin\Plugin\Network\OauthClient;

/**
 * LinkedInOAuthLoginController Class
 */
class LinkedInOAuthLoginController extends ControllerBase {
  /**
   * {@inheritdoc}
   */
  public function linkedinOAuthUserLogin() {
    $this->linkedin_oauth_user_login();
    return $this->redirect('<front>');
  }
  
  /**
   * {@inheritdoc}
   */
  public function linkedin_oauth_user_login() {
    $config = \Drupal::config('linkedin_oauth_login.settings');
    //LinkedIn client ID.
    $apiKey = $config->get('client_id');
    //LinkedIn client secret.
    $apiSecret = $config->get('client_secret');
    //Callback URL.
    $redirectURL = $config->get('redirect_url');

    $scope = 'r_basicprofile r_emailaddress'; 
    //Call LinkedIn API.
    $client = new OauthClient;
  
    $client->client_id = $apiKey;
    $client->client_secret = $apiSecret;
    $client->redirect_uri = $redirectURL;
    $client->scope = $scope;
    $client->debug = false;
    $client->debug_http = true;
    $application_line = __LINE__;
        
    if(strlen($client->client_id) == 0 || strlen($client->client_secret) == 0) {
      die('Please go to LinkedIn Apps page https://www.linkedin.com/secure/developer?newapp= , '.
        'create an application, and in the line '.$application_line.
        ' set the client_id to Consumer key and client_secret with Consumer secret. '.
        'The Callback URL must be '.$client->redirect_uri.'. Make sure you enable the '.
        'necessary permissions to execute the API calls your application needs.');
    }

    //If authentication returns success.
    if($success = $client->Initialize()) {
      if(($success = $client->Process())) {         
        if(strlen($client->authorization_error)) {
          $client->error = $client->authorization_error;
          $success = false;
        }
        elseif(strlen($client->access_token)) {
          $success = $client->CallAPI('http://api.linkedin.com/v1/people/~:(id,email-address,first-name,last-name,location,picture-url,public-profile-url,formatted-name)', 
          'GET',
          array('format'=>'json'),
          array('FailOnAccessError'=>true), $userInfo);
        }
      }       
      $success = $client->Finalize($success);
    }
    if($client->exit) exit;
    if($success) {
      //Initialize User class.
      $this->userExist($userInfo);
    }
    else{
      //Error message.
      drupal_set_message('Error connecting to LinkedIn! try again later!', 'error');
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function userExist($userInfo) {
    $email    = $userInfo->emailAddress;
    $userData = user_load_by_mail($email);
    if (empty($userData)) {
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
      $user = \Drupal\user\Entity\User::create();

      //Mandatory settings.
      $user->setPassword(FALSE);
      $user->enforceIsNew();
      $user->setEmail($email);
      $user->setUsername($email); 

      //Optional settings.    
      $user->set("init", 'email');
      $user->set("langcode", $language);
      $user->set("preferred_langcode", $language);
      $user->set("preferred_admin_langcode", $language);
      $user->activate();
      //Save user.
      $user->save();
      user_login_finalize($user);
    } 
    else {
      user_login_finalize($userData);
    }
  }

} 