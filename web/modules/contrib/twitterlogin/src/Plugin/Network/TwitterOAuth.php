<?php

namespace Drupal\twitterlogin\Plugin\Network;

use Drupal\twitterlogin\Plugin\Network\OAuthSignatureMethod_HMAC_SHA1;
use Drupal\twitterlogin\Plugin\Network\OAuthConsumer;
use Drupal\twitterlogin\Plugin\Network\OAuthUtil;
use Drupal\twitterlogin\Plugin\Network\OAuthRequest;

use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * Twitter OAuth class
 */
class TwitterOAuth {
  /* Contains the last HTTP status code returned. */
  public $http_code;
  /* Contains the last API call. */
  public $url;
  /* Set up the API root URL. */
  public $host = "https://api.twitter.com/1.1/";
  /* Set timeout default. */
  public $timeout = 30;
  /* Set connect timeout. */
  public $connecttimeout = 30; 
  /* Verify SSL Cert. */
  public $ssl_verifypeer = FALSE;
  /* Respons format. */
  public $format = 'json';
  /* Decode returned json data. */
  public $decode_json = TRUE;
  /* Contains the last HTTP headers returned. */
  public $http_info;
  /* Set the useragnet. */
  public $useragent = 'TwitterOAuth v0.2.0-beta2';
  /* Immediately retry the API call if the response was not successful. */
  /**
   * Set API URLS.
   */
  function accessTokenURL()  { return 'https://api.twitter.com/oauth/access_token'; }
  function authenticateURL() { return 'https://api.twitter.com/oauth/authenticate'; }
  function authorizeURL()    { return 'https://api.twitter.com/oauth/authorize'; }
  function requestTokenURL() { return 'https://api.twitter.com/oauth/request_token'; }
  
  /**
   * Debug helpers.
   */
  function lastStatusCode() { return $this->http_status; }
  function lastAPICall() { return $this->last_api_call; }

  /**
   * Construct TwitterOAuth object.
   */
  function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {
      $this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
      $this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
      if (!empty($oauth_token) && !empty($oauth_token_secret)) {
          $this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
      } else {
          $this->token = NULL;
      }
    }
  
  
  /**
   * Get a request_token from Twitter.
   *
   * @returns a key/value array containing oauth_token and oauth_token_secret
   */
  function getRequestToken($oauth_callback) {
      $parameters = [];
      $parameters['oauth_callback'] = $oauth_callback;
      $request = $this->oAuthRequest($this->requestTokenURL(), 'GET', $parameters);
      $token = OAuthUtil::parse_parameters($request);
      $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
      return $token;
  }
  
  /**
   * Get the authorize URL.
   *
   * @returns a string
   */
  function getAuthorizeURL($token, $sign_in_with_twitter = TRUE) {
      if (is_array($token)) {
          $token = $token['oauth_token'];
      }
      if (empty($sign_in_with_twitter)) {
          return $this->authorizeURL() . "?oauth_token={$token}";
      } 
      else {
          return $this->authenticateURL() . "?oauth_token={$token}&force_login=true";
      }
  }

  /**
   * Exchange request token and secret for an access token and
   * secret, to sign API calls.
   *
   * @returns array("oauth_token" => "the-access-token",
   *                "oauth_token_secret" => "the-access-secret",
   *                "user_id" => "1234567",
   *                "screen_name" => "codexworld")
   */
  function getAccessToken($oauth_verifier) {
      $parameters = [];
      $parameters['oauth_verifier'] = $oauth_verifier;
      $request = $this->oAuthRequest($this->accessTokenURL(), 'GET', $parameters);
      $token = OAuthUtil::parse_parameters($request);
      $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
      return $token;
  }

  /**
   * One time exchange of username and password for access token and secret.
   *
   * @returns array("oauth_token" => "the-access-token",
   *                "oauth_token_secret" => "the-access-secret",
   *                "user_id" => "1234567",
   *                "screen_name" => "codexworld",
   *                "x_auth_expires" => "0")
   */  
  function getXAuthToken($username, $password) {
      $parameters = [];;
      $parameters['x_auth_username'] = $username;
      $parameters['x_auth_password'] = $password;
      $parameters['x_auth_mode'] = 'client_auth';
      $request = $this->oAuthRequest($this->accessTokenURL(), 'POST', $parameters);
      $token = OAuthUtil::parse_parameters($request);
      $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
      return $token;
  }

  /**
   * GET wrapper for oAuthRequest.
   */
  function get($url, $parameters = array()) {
      $response = $this->oAuthRequest($url, 'GET', $parameters);
      if ($this->format === 'json' && $this->decode_json) {
          return json_decode($response);
      }
      return $response;
  }
  
  /**
   * POST wrapper for oAuthRequest.
   */
  function post($url, $parameters = array()) {
      $response = $this->oAuthRequest($url, 'POST', $parameters);
      if ($this->format === 'json' && $this->decode_json) {
          return json_decode($response);
      }
      return $response;
  }

  /**
   * DELETE wrapper for oAuthReqeust.
   */
  function delete($url, $parameters = array()) {
      $response = $this->oAuthRequest($url, 'DELETE', $parameters);
      if ($this->format === 'json' && $this->decode_json) {
          return json_decode($response);
      }
      return $response;
  }

  /**
   * Format and sign an OAuth / API request.
   */
  function oAuthRequest($url, $method, $parameters) {
    if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0) {
      $url = "{$this->host}{$url}.{$this->format}";
    }
    $request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
  
    $request->sign_request($this->sha1_method, $this->consumer, $this->token);
    switch ($method) {
      case 'GET':
        return $this->http($request->to_url(), 'GET');
      default:
        return $this->http($request->get_normalized_http_url(), $method, $request->to_postdata());
    }
  }

  /**
   * Make an HTTP request.
   *
   * @return API results
   */
  function http($url, $method, $postfields = NULL) {
      $this->http_info = [];
      $ci = curl_init();
      /* Curl settings */
      curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
      curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
      curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
      curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
      curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
      curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
      curl_setopt($ci, CURLOPT_HEADER, FALSE);
  
      switch ($method) {
          case 'POST':
            curl_setopt($ci, CURLOPT_POST, TRUE);
            if (!empty($postfields)) {
                curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
            }
            break;
          case 'DELETE':
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
            if (!empty($postfields)) {
                $url = "{$url}?{$postfields}";
            }
      }
  
      curl_setopt($ci, CURLOPT_URL, $url);
      $response = curl_exec($ci);
      $this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
      $this->http_info = array_merge($this->http_info, curl_getinfo($ci));
      $this->url = $url;
      curl_close ($ci);
      return $response;
  }

  /**
   * Get the header info to store.
   */
  function getHeader($ch, $header) {
    $i = strpos($header, ':');
    if (!empty($i)) {
        $key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
        $value = trim(substr($header, $i + 2));
        $this->http_header[$key] = $value;
    }
    return strlen($header);
  }

  /**
   * {@inheritdoc}
   */
  public static function requestToken($consumerKey,$consumerSecret,$redirectURL){
    $twClient = new TwitterOAuth($consumerKey, $consumerSecret);
    $request_token = $twClient->getRequestToken($redirectURL);

    session_start();
    //Received token info from twitter
    $_SESSION['token']     = $request_token['oauth_token'];
    $_SESSION['token_secret']= $request_token['oauth_token_secret'];

    //If authentication returns success
    if($twClient->http_code == '200'){
      //Get twitter oauth url
      return $twClient->getAuthorizeURL($request_token['oauth_token']);
    }
    else {
      return '404';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function accessTokenTwitter($consumerKey, $consumerSecret, $token , $token_secret) {
    //Call Twitter API.
    $twClient = new TwitterOAuth($consumerKey, $consumerSecret, $token , $token_secret);      
    //Get OAuth token.
    $access_token = $twClient->getAccessToken($_REQUEST['oauth_verifier']);
    if($twClient->http_code == '200'){
      //Storing access token data into session.
      $_SESSION['status'] = 'verified';
      $_SESSION['request_vars'] = $access_token;

      $params = array('include_email' => TRUE, 'include_entities' => FALSE, 'skip_status' => TRUE);
      
      //Get user profile data from twitter.
      $userInfo = $twClient->get('account/verify_credentials', $params);
      return $userInfo;
    }
    else {
      return '404';
    }
  }

  /**
	  * Logged into User Profile Page.
	  */
	public static function social_login_twitter_login($userInfo) {  
	  global $base_url;

  	if (isset($userInfo->screen_name)) {
      $accounts = \Drupal::entityManager()
        ->getStorage('user')
        ->loadByProperties(array(
          'name' => $userInfo->screen_name,
          'status' => 1
        ));
      $account = reset($accounts);
      if (is_object($account)) {
        user_login_finalize($account);
        return new RedirectResponse($base_url . '/user/' . $account->id() . '/edit');
      }
      else {
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $user = \Drupal\user\Entity\User::create();

        //Mandatory settings.
        $user->setPassword(FALSE);
        $user->enforceIsNew();
        $user->setEmail($userInfo->screen_name);
        //This username must be unique and accept only a-Z,0-9, - _ @ .
        $user->setUsername($userInfo->screen_name); 

        //Optional settings.    
        $user->set("init", 'email');
        $user->set("langcode", $language);
        $user->set("preferred_langcode", $language);
        $user->set("preferred_admin_langcode", $language);
        $user->activate();
        //Save user.
        $user->save();

        // Programmatically create files.
        // Create file object from a locally copied file.
        $uri  = file_unmanaged_copy($userInfo->profile_image_url, 'public://user/'.$user->Id().'.jpg', FILE_EXISTS_REPLACE);
        $file = File::Create([
          'uri' => $uri,
        ]);
        $file->save();

        $user->set("user_picture", $file);

        user_login_finalize($user);
        return new RedirectResponse($base_url . '/user');
      }
    }
    else {
      return '404';
    }
	}
}
