<?php

/**
 * @file
 * Contains \Drupal\sjisocialconnect\Controller\TwitterController.
 */

namespace Drupal\sjisocialconnect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Plugin\Oauth\OauthPlugin;

/**
 * Returns responses for Twitter API.
 */
class TwitterController extends ControllerBase {

  /**
   * The Application ID.
   *
   * @var string
   */
  public $consumer_key;
  
  /**
   * The Application App Secret.
   *
   * @var string
   */
  public $consumer_secret;
  
  /**
   * The client token.
   *
   * @var string
   */
  public $oauth_token;
  
  /**
   * The client secret token.
   *
   * @var string
   */
  public $oauth_token_secret;
  
  /**
   * The Twitter config.
   */
  public $sjisocialconnect_twitter;
  
  /**
   * Constructs a Publish Content Twitter object.
   */
  public function getConfig($default_values = array()) {
    if (empty($default_values)) {
      $this->sjisocialconnect_twitter = \Drupal::config('sjisocialconnect.twitter')->getRawData();
      if (!empty($this->sjisocialconnect_twitter)) {
        $twitter_conf = $this->sjisocialconnect_twitter['twitter'];
        foreach ($twitter_conf as $key => $value) {
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
    
    // Twitter settings.
    $form['twitter'] = array(
      '#type' => 'details',
      '#title' => t('Twitter settings'),
      '#open' => \Drupal::service('path.matcher')->matchPath(Url::fromRoute('<current>'), '/admin/config/services/publish-away/*'),
    );
    $form['twitter']['consumer_key'] = array(
      '#title' => t('Consumer key'),
      '#type' => 'textfield',
      '#default_value' => $this->consumer_key,
      '#required' => FALSE,
      '#access' => $access,
    );
    $form['twitter']['consumer_secret'] = array(
      '#title' => t('Consumer secret key'),
      '#type' => 'textfield',
      '#default_value' => $this->consumer_secret,
      '#required' => FALSE,
      '#access' => $access,
    );
    $form['twitter']['oauth_token'] = array(
      '#title' => t('Access token'),
      '#type' => 'textfield',
      '#default_value' => $this->oauth_token,
      '#required' => FALSE,
      '#access' => $access,
    );
    $form['twitter']['oauth_token_secret'] = array(
      '#title' => t('Access token secret'),
      '#type' => 'textfield',
      '#default_value' => $this->oauth_token_secret,
      '#required' => FALSE,
      '#access' => $access,
    );
    $form['twitter']['html'] = array(
      '#type' => 'markup',
      '#markup' => self::login($default_values),
    );
    
    return $form;
  }

  /**
   *  Login user to Twitter.
   * @param array $default_values
   * @return string
   */
  private function login($default_values = array()) {
    self::getConfig($default_values);
    $twitter_html = '';

    if ($this->consumer_key != '' && $this->consumer_secret != '' && $this->oauth_token != '' && $this->oauth_token_secret != '') {
      
      // Create a client to work with the Twitter API.
      $client = new Client('https://api.twitter.com/1.1', array(
        'version' => '1.1'
      ));

      // Sign all requests with the OauthPlugin.
      $client->addSubscriber(new OauthPlugin(array(
        'consumer_key'  => $this->consumer_key,
        'consumer_secret' => $this->consumer_secret,
        'token'       => $this->oauth_token,
        'token_secret'  => $this->oauth_token_secret
      )));

      try {
        $response = $client->get('account/verify_credentials.json')->send()->json();
        $twitter_user = (object) $response;
        if (isset($twitter_user->id) && trim($twitter_user->id) != '') {
          $twitter_html .= '<strong>' . t('Twitter account') . ' :</strong> ' . $twitter_user->name;
          $twitter_html .= '<br /><img style="veryical-align:middle;" src="'. $twitter_user->profile_image_url .'" alt="'. t('@user image', array('@user' => $twitter_user->name)) .'" />';
          $twitter_url = Url::fromUri("https://twitter.com/$twitter_user->screen_name");
          $twitter_html .= '<br /><strong>' . \Drupal::l(t('Visit your Twitter'), $twitter_url) . '</strong>';
        }
      }
      catch (ClientErrorResponseException $e) {
        drupal_set_message("Twitter exception occured, with message: " . $e->getMessage(), 'error');
      }
    }
    elseif (!empty($this->consumer_key)) {
      $twitter_html = t('Twitter connection failed.');
    }
    else {
      $twitter_url = Url::fromUri('https://apps.twitter.com');
      $twitter_html .= '<br /><strong>' . \Drupal::l(t('Twitter API'), $twitter_url) . '</strong>';
    }
    
    return $twitter_html;
  }
  
  /**
    * Send post to Twitter.
    * 
    * @params array
    *  'consumer_key' => string,
    *  'consumer_secret' => string,
    *  'oauth_token' => string,
    *  'oauth_token_secret' => string,
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
     }

     if (trim($this->consumer_key) != '' && trim($this->consumer_secret) != '' && trim($this->oauth_token) != '' && trim($this->oauth_token_secret) != '') {

       // Create a client to work with the Twitter API.
       $client = new Client('https://api.twitter.com/1.1', array(
         'version' => '1.1'
       ));

       // Sign all requests with the OauthPlugin.
       $client->addSubscriber(new OauthPlugin(array(
         'consumer_key'  => $this->consumer_key,
         'consumer_secret' => $this->consumer_secret,
         'token'       => $this->oauth_token,
         'token_secret'  => $this->oauth_token_secret
       )));

       try {
         $response = $client->get('account/verify_credentials.json')->send()->json();
         $twitter_user = (object) $response;
         if (isset($twitter_user->id) && trim($twitter_user->id) != '') {
           $filename = NULL;
           // $filemime = 'image/png';
           if (!empty($FileUri)) {
             // $tweet_poster_imgpath = explode('://', $FileUri);
             // $filename = drupal_realpath(file_stream_wrapper_get_instance_by_uri('public://')->getDirectoryPath() . '/' . $tweet_poster_imgpath[1] . '');
             $filename = drupal_realpath($FileUri);
             // $ext = explode('.', $tweet_poster_imgpath[1]);
             // $filemime = 'image/' . $ext[1];

             $url = 'statuses/update_with_media.json';
             $params = array(
               // 'media' => "@{$filename};type={$filemime};filename={$filename}",
               'media' => "@{$filename}",
               'status' => $message,
             );
           }
           else {
             $url = 'statuses/update.json';
             $params = array(
               'status' => $message,
             );
           }
           // Create a tweet using POST.
           $request = $client->post($url, null, $params);
           // Send the request and parse the JSON response into an array.
           $data = $request->send()->json();
           drupal_set_message(t('Your twitter has been posted successfully.'));
         }
       }
       catch (Exception $e) {
         drupal_set_message("Twitter exception occured, with message: " . $e->getMessage(), 'error');
       }
     }
   }
  
}
