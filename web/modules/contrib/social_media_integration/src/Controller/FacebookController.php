<?php

/**
 * @file
 * Contains \Drupal\sjisocialconnect\Controller\FacebookController.
 * SDK Facebook.
 */

namespace Drupal\sjisocialconnect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Facebook\FacebookSession;
use Facebook\FacebookRedirectLoginHelper;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
use Facebook\GraphUser;
use Facebook\FacebookSDKException;

/**
 * Returns responses for Facebook API.
 */
class FacebookController extends ControllerBase {

  /**
   * The Application ID.
   *
   * @var string
   */
  public $appId = null;
  
  /**
   * The Application App Secret.
   *
   * @var string
   */
  public $appSecret = null;
  
  /**
   * The client token.
   *
   * @var string
   */
  public $client_token;
  
  /**
   * The ID of the Facebook user, or 0 if the user is logged out.
   *
   * @var string
   */
  public $user;
  
  /**
   * The Facebook user name.
   *
   * @var string
   */
  public $name;
  
  /**
   * The Facebook user scopes.
   *
   * @var string
   */
  public $scopes;
  
  /**
   * The ID of the Facebook page.
   *
   * @var string
   */
  public $page_id;
  
  /**
   * The OAuth access token received in exchange for a valid authorization
   * code.  null means the access token has yet to be determined.
   *
   * @var string
   */
  public $token = null;
  
  /**
   * The Facebook config.
   */
  public $sjisocialconnect_facebook;

  /**
   * Constructs a Publish Content Facebook object.
   */
  public function getConfig($default_values = array()) {
    if (empty($default_values)) {
      $this->sjisocialconnect_facebook = \Drupal::config('sjisocialconnect.facebook')->getRawData();
      if (!empty($this->sjisocialconnect_facebook)) {
        $facebook_conf = $this->sjisocialconnect_facebook['facebook'];
        foreach ($facebook_conf as $key => $value) {
          $this->$key = $value;
        }
      }
    }
    else {
      foreach ($default_values as $key => $value) {
        $this->$key = $value;
      }
    }
  }
  
  public function formElement($default_values = array()) {
    self::getConfig($default_values);
    $access = \Drupal::currentUser()->hasPermission('administer sji social connect');
    $form = array();
    
    // Facebook settings.
    $form['facebook'] = array(
      '#type' => 'details',
      '#title' => t('Facebook settings'),
      '#open' => \Drupal::service('path.matcher')->matchPath(Url::fromRoute('<current>'), '/admin/config/services/publish-away/*'),
    );
    $form['facebook']['appId'] = array(
      '#title' => t('AppId'),
      '#type' => 'textfield',
      '#default_value' => $this->appId,
      '#required' => FALSE,
      '#access' => $access,
    );
    $form['facebook']['appSecret'] = array(
      '#title' => t('Secret key'),
      '#type' => 'textfield',
      '#default_value' => $this->appSecret,
      '#required' => FALSE,
      '#access' => $access,
    );
    $form['facebook']['client_token'] = array(
      '#title' => t('Client Token'),
      '#type' => 'textfield',
      '#default_value' => $this->client_token,
      '#access' => $access,
    );
    $form['facebook']['page_id'] = array(
      '#title' => t('Facebook Page ID'),
      '#type' => 'textfield',
      '#default_value' => $this->page_id,
      '#required' => FALSE,
      '#access' => $access,
    );
    $form['facebook']['token'] = array(
      '#type' => 'hidden',
      '#default_value' => !empty($this->token) ? $this->token : NULL,
      '#disabled' => TRUE,
      '#access' => $access,
    );
    $form['facebook']['html'] = array(
      '#type' => 'markup',
      '#markup' => self::login($default_values),
    );
    
    return $form;
  }
  
  /**
   * Login user to Facebook.
   * @param array $default_values
   * @global string $base_url
   * @return string | html
   */
  public function login($default_values = array()) {
    self::getConfig($default_values);
    global $base_url;
    // Facebook connect.
    $facebook_html = '';
    
    if (empty($this->appId) || empty($this->appSecret)) {
      //\Drupal::config('sjisocialconnect.facebook')->set('facebook.token', NULL)->save();
      $config = \Drupal::service('config.factory')->getEditable('sjisocialconnect.facebook');
      $config->set('facebook.token', NULL)->save();
      $facebook_url = Url::fromUri('https://developers.facebook.com/apps');
      $facebook_html .= '<br /><strong>' . \Drupal::l(t('Facebook API'), $facebook_url) . '</strong>';
      return $facebook_html;
    }
    
    // Init app with app id and appSecret.
    try {
      FacebookSession::setDefaultApplication($this->appId, $this->appSecret);
      // Login helper with redirect_uri.
      $helper = new FacebookRedirectLoginHelper($base_url . Url::fromRoute('<current>'));
    }
    catch (FacebookSDKException $e) {
      // Catch any exceptions.
      drupal_set_message("Facebook exception occured, code: " . $e->getCode(). " with message: " . $e->getMessage(), 'error');
    }
    $fb_token = $this->token;
    // See if a existing session exists.
    if (!empty($fb_token)) {
      // Create new session from saved access_token.
      $session = new FacebookSession($fb_token);

      // Validate the access_token to make sure it's still valid.
      try {
        if (!$session->validate()) {
          $session = null;
        }
      }
      catch (FacebookRequestException $e) {
        // Catch any exceptions.
        $session = null;
        drupal_set_message("Facebook exception occured, code: " . $e->getCode(). " with message: " . $e->getMessage(), 'error');
      }
    }  

    if (!isset($session) || $session === null) {
      // No session exists.
      try {
        $session = $helper->getSessionFromRedirect();
      }
      catch(FacebookRequestException $e) {
        // When Facebook returns an error
        // handle this better in production code.
        drupal_set_message("Facebook exception occured, code: " . $e->getCode(). " with message: " . $e->getMessage(), 'error');
      }
      catch(\Exception $e) {
        // When validation fails or other local issues
        // handle this better in production code.
        drupal_set_message("Facebook exception occured, code: " . $e->getCode(). " with message: " . $e->getMessage(), 'error');
      }
    }

    // See if we have a session.
    if (isset($session) && !empty($session)) {
      // Create a session using saved token or the new one we generated at login.
      $session = new FacebookSession($session->getToken());
      
      try {
        $user_profile = (new FacebookRequest(
          $session,
          'GET',
          '/me'
        ))->execute()->getGraphObject(GraphUser::className());
        $info = $session->getSessionInfo();
        // Save user_profile data.
        \Drupal::config('sjisocialconnect.facebook')
          ->set('facebook.id', $user_profile->getId())
          ->set('facebook.name', $user_profile->getName())
          ->set('facebook.scopes', $info->getScopes())
          ->set('facebook.token', $session->getToken())
          ->save();
        
        $this->token = $session->getToken();
        $facebook_html .= '<strong>' . t('Facebook username') . ' :</strong> ' . $user_profile->getName();
        $facebook_url = Url::fromUri($user_profile->getLink());
        $facebook_html .= '<br /><strong>' . \Drupal::l(t('Visit your Facebook'), $facebook_url) . '</strong>';
        // $logout_url = $helper->getLogoutUrl($session, $base_url . Url::fromRoute('<current>'));
        // $facebook_html .= '<br /><strong><a href="'.$logout_url.'">' . t('Logout') . "</a></strong>";
      }
      catch(FacebookRequestException $e) {
        \Drupal::config('sjisocialconnect.facebook')->set('facebook.token', NULL)->save();
        drupal_set_message("Facebook exception occured, code: " . $e->getCode(). " with message: " . $e->getMessage(), 'error');
      }
    }
    else {
      // Show login url.
      $login_url = $helper->getLoginUrl(array('email', 'user_friends'));
      $facebook_html .= '<a href="'.$login_url.'">' . t('You need to connect to tour Facebook account') . "</a>";
      \Drupal::config('sjisocialconnect.facebook')->set('facebook.token', NULL)->save();
    }
    
    return $facebook_html;
  }
  
/**
 * Send post to Facebook.
 * 
 * @params array
 *  'appId' => string,
 *  'appSecret' => string,
 *  'entity_type' => string,
 *  'bundle' => string,
 *  'entity_id' => int,
 *  'message' => long text,
 *  'images_uri' => array of fids
 * @return string
 *  The post id created or NULL otherwise.
 */
  public function post($params = array()) {
    $message = isset($params['message']) ? $params['message'] : NULL;
    $FileUri = isset($params['images_uri']) && !empty($params['images_uri'])? reset($params['images_uri']) : NULL;
    
    if (!empty($params['config'])) {
      self::getConfig($params['config']);
      self::login($params['config']);
    }
    
    global $base_url;
    $post_id = NULL;
    
    if (empty($this->appId) || empty($this->appSecret)) {
      // $this->config('sjisocialconnect.facebook')->set('facebook.token', NULL)->save();
      $this->token = null;
      return $post_id;
    }

    // Init app with app id and appSecret.
    try {
      FacebookSession::setDefaultApplication($this->appId, $this->appSecret);
      // Login helper with redirect_uri.
      $helper = new FacebookRedirectLoginHelper($base_url . Url::fromRoute('<current>'));
    }
    catch (FacebookRequestException $e) {
      // Catch any exceptions.
      drupal_set_message("Facebook exception occured, code: " . $e->getCode(). " with message: " . $e->getMessage(), 'error');
    }
    
    $fb_token = $this->token;
    // See if a existing session exists.
    if (!empty($fb_token)) {
      // Create new session from saved access_token.
      $session = new FacebookSession($fb_token);

      // Validate the access_token to make sure it's still valid.
      try {
        if (!$session->validate()) {
          $session = null;
        }
      }
      catch (FacebookSDKException $e) {
        // Catch any exceptions.
        $session = null;
        drupal_set_message("Facebook exception occured, code: " . $e->getCode(). " with message: " . $e->getMessage(), 'error');
      }
    }  

    if (!isset($session) || $session === null) {
      // No session exists.
      try {
        $session = $helper->getSessionFromRedirect();
      }
      catch(FacebookRequestException $ex) {
        // When Facebook returns an error
        // handle this better in production code.
        drupal_set_message($ex->getMessage(), 'error');
      }
      catch(\Exception $ex) {
        // When validation fails or other local issues
        // handle this better in production code.
        drupal_set_message("Facebook exception occured, code: " . $e->getCode(). " with message: " . $e->getMessage(), 'error');
      }
    }

    // If session is valid go forward.
    if (is_object($session) && $session->validate()) {
      try {

        $fb_image = array();
        
        if (isset($FileUri) && !empty($FileUri) && trim($FileUri) != '') {
          $fb_image = array('picture' => file_create_url($FileUri));
          // $fb_image = array('source' => '@' . drupal_realpath($FileUri));
        }

        // Post to a facebook page if any.
        $page_id = trim($this->page_id);
        $fb_account_name = t('Facebook account');
        $this->token = $session->getToken();

        $parameters = array(
          'access_token'  => $session->getToken(),
          'message'       => $message,
          'link'         => $base_url . \Drupal::urlGenerator()->generateFromPath($params['entity_type'].'/' . $params['entity_id']),
        ) + $fb_image;
        
        if (!empty($page_id) && (int) $page_id) {
          $fb_account_name = t('Facebook page');
          $response = (new FacebookRequest($session, 'POST', "/$page_id/feed", $parameters))->execute()->getGraphObject();
        }
        // Post to the main timeline account.
        else {
          $fb_account_name = t('Facebook account');
          $response = (new FacebookRequest($session, 'POST', "/me/feed", $parameters))->execute()->getGraphObject();
        }

        $post_id = $response->getProperty('id');
      }
      catch(FacebookRequestException $e) {
        drupal_set_message("Facebook exception occured, code: " . $e->getCode(). " with message: " . $e->getMessage(), 'error');
      }
    }
    else {
      drupal_set_message(t("Facebook exception occured, your IP can't make requests for that application or your Facebook API details are incorrect."), 'error');
    }

    if ((int) $post_id) {
      drupal_set_message(t('Your post has been send successfully on your @account.', array('@account' => $fb_account_name)));
      return $post_id;
    }

    return $post_id;
  }

}
