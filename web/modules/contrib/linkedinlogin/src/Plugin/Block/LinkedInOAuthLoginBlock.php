<?php

namespace Drupal\linkedinlogin\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\block\Annotation\Block;
use Drupal\block\Annotation\Translation;

use Drupal\linkedinlogin\Plugin\Network\HttpClass;
use Drupal\linkedinlogin\Plugin\Network\OauthClient;


/**
 * Provides a LinkedIn OAuth Login Block
 *
 * @Block(
 *   id = "linkedin_oauth_login_block",
 *   admin_label = @Translation("LinkedIn OAuth Login"),
 *   category = @Translation("Blocks")
 * )
 */
class LinkedInOAuthLoginBlock extends BlockBase {
 	
 	/**
   * {@inheritdoc}
   */
  public function build() {

  	$authUrl = $output = '';
  	$config = \Drupal::config('linkedin_oauth_login.settings');
    //Google client ID.
    $apiKey = $config->get('client_id');    
    //Google client secret.
    $apiSecret = $config->get('client_secret');
    //Callback URL.
    $redirectURL = $config->get('redirect_url');;
    //API permissions.
    $scope = 'r_basicprofile r_emailaddress'; 
		//Call Google API.

    $display = \Drupal::config('linkedinlogin.icon.settings')->get('display');
	  $display_url = \Drupal::config('linkedinlogin.icon.settings')->get('display_url');

	  $path = drupal_get_path('module', 'linkedinlogin');

	  if (isset($display_url) && $display_url!='') {
	    $iconUrl = '<img src = '.$display_url.' />';
	  }
	  else {
	    if ($display == 0) {
	      $iconUrl = '<img src = "/'. $path .'/images/sign-in-with-linkedin.png" border="0">';
	    }
	    if ($display == 1) {         
	      $iconUrl = '<img src = "/'. $path .'/images/linkedin-logo.png.png" border="0">';
	    }
	    if ($display == 2) {         
	      $iconUrl = '<img src = "/'. $path .'/images/linkedin-logo-512x512.png" border="0">';
	    }
	  }

	  $oauth_init = \Drupal::request()->query->get('oauth_init');
	  $oauth_token = \Drupal::request()->query->get('oauth_token');
	  $oauth_problem = \Drupal::request()->query->get('oauth_problem');

	  $userCurrent = \Drupal::currentUser();
    if ($userCurrent->isAuthenticated()) {
    	$output = 'Block is empty';
	  }
	  else if((isset($oauth_init) && $oauth_init == 1) || (isset($oauth_token) && isset($oauth_token))) {
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
      	$output = '';
			}
			else{
				//Error message.
				drupal_set_message('Error connecting to LinkedIn! try again later!', 'error');
			}
	  }
	  elseif(isset($oauth_problem) && $oauth_problem <> ""){
	  	//Error message.
	  	drupal_set_message($oauth_problem, 'error');
		}
	  else {
	  	$authUrl = '?oauth_init=1';
	  	$output = '<a href = "'.$authUrl.'">'.$iconUrl.'</a>';
	  }   
    
    return array(
      '#markup' => $output,
      '#cache' => array(
        'max-age' => 0
      )
    );
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
