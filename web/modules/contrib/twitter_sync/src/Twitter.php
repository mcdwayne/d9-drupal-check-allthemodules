<?php

namespace Drupal\twitter_sync;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class Twitter.
 */
class Twitter {

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Access token.
   *
   * @var string
   */
  private $oauthAccessToken;

  /**
   * Access token secret.
   *
   * @var string
   */
  private $oauthAccessTokenSecret;

  /**
   * Consumer key.
   *
   * @var string
   */
  private $consumerKey;

  /**
   * Consumer secret.
   *
   * @var string
   */
  private $consumerSecret;

  /**
   * Post fields.
   *
   * @var array
   */
  private $postfields;

  /**
   * Get fields.
   *
   * @var string
   */
  private $getfield;

  /**
   * Oauth object.
   *
   * @var mixed
   */
  protected $oauth;

  /**
   * Url to be called on API.
   *
   * @var string
   */
  public $url;

  /**
   * Method request.
   *
   * @var string
   */
  public $requestMethod;

  /**
   * Screen name.
   *
   * @var string
   */
  private $screenName;

  /**
   * Tweet count.
   *
   * @var string
   */
  private $tweetCount;

  /**
   * Create the API access object.
   *
   * Requires the cURL library.
   *
   * @throws \Exception
   *   When cURL isn't installed or incorrect settings parameters are provided.
   */
  public function __construct(ConfigFactoryInterface $config, LoggerChannelFactory $logger) {
    $this->loggerFactory = $logger->get('twitter_sync');
    if (!in_array('curl', get_loaded_extensions())) {
      $msg = 'You need to install cURL, see: http://curl.haxx.se/docs/install.html';
      $this->loggerFactory->notice($msg);
    }

    $config_twitter = $config->get('twitter_sync_settup_form.settings');
    $this->screenName = $config_twitter->get('field_twitter_screen_name');
    $this->tweetCount = 50;
    $this->oauthAccessToken = $config_twitter->get('field_twitter_sync_access_token');
    $this->oauthAccessTokenSecret = $config_twitter->get('field_twitter_sync_access_token_secret');
    $this->consumerKey = $config_twitter->get('field_twitter_sync_consumer_key');
    $this->consumerSecret = $config_twitter->get('field_twitter_sync_consumer_secret');
  }

  /**
   * Set postfields array, example: array('screen_name' => 'J7mbo')
   *
   * @param array $array
   *   Array of parameters to send to API.
   *
   * @throws \Exception
   *   When you are trying to set both get and post fields.
   *
   * @return TwitterAPIExchange
   *   Instance of self for method chaining
   */
  public function setPostfields(array $array) {
    if (!is_null($this->getGetfield())) {
      $msg = 'You can only choose get OR post fields.';
      $this->loggerFactory->notice($msg);
    }
    if (isset($array['status']) && substr($array['status'], 0, 1) === '@') {
      $array['status'] = sprintf("\0%s", $array['status']);
    }
    foreach ($array as &$value) {
      if (is_bool($value)) {
        $value = ($value === TRUE) ? 'true' : 'false';
      }
    }
    $this->postfields = $array;
    // Rebuild oAuth.
    if (isset($this->oauth['oauth_signature'])) {
      $this->buildOauth($this->url, $this->requestMethod);
    }
    return $this;
  }

  /**
   * Set getfield string, example: '?screen_name=J7mbo'.
   *
   * @param string $string
   *   Get key and value pairs as string.
   *
   * @throws \Exception
   *
   * @return \TwitterAPIExchange
   *   Instance of self for method chaining
   */
  public function setGetfield($string) {
    if (!is_null($this->getPostfields())) {
      $msg = 'You can only choose get OR post fields.';
      $this->loggerFactory->notice($msg);
    }
    $getfields = preg_replace('/^\?/', '', explode('&', $string));
    $params = [];
    foreach ($getfields as $field) {
      if ($field !== '') {
        list($key, $value) = explode('=', $field);
        $params[$key] = $value;
      }
    }
    $this->getfield = '?' . http_build_query($params);
    return $this;
  }

  /**
   * Get getfield string (simple getter).
   *
   * @return string
   *   Getfields.
   */
  public function getGetfield() {
    return $this->getfield;
  }

  /**
   * Get postfields array (simple getter).
   *
   * @return array
   *   Return postfields.
   */
  public function getPostfields() {
    return $this->postfields;
  }

  /**
   * Build the Oauth object.
   *
   * @param string $url
   *   The API url to use. Ex: https://api.twitter.com/1.1/search/tweets.json.
   * @param string $requestMethod
   *   Either POST or GET.
   *
   * @throws \Exception
   *
   * @return \TwitterAPIExchange
   *   Instance of self for method chaining.
   */
  public function buildOauth($url, $requestMethod) {
    if (!in_array(strtolower($requestMethod), ['post', 'get'])) {
      $this->loggerFactory->notice('Request method must be either POST or GET');
    }
    $consumer_key = $this->consumerKey;
    $consumer_secret = $this->consumerSecret;
    $oauth_access_token = $this->oauthAccessToken;
    $oauth_access_token_secret = $this->oauthAccessTokenSecret;
    $oauth = [
      'oauth_consumer_key' => $consumer_key,
      'oauth_nonce' => time(),
      'oauth_signature_method' => 'HMAC-SHA1',
      'oauth_token' => $oauth_access_token,
      'oauth_timestamp' => time(),
      'oauth_version' => '1.0',
    ];
    $getfield = $this->getGetfield();
    if (!is_null($getfield)) {
      $getfields = str_replace('?', '', explode('&', $getfield));
      foreach ($getfields as $g) {
        $split = explode('=', $g);
        // In case a null is passed through.
        if (isset($split[1])) {
          $oauth[$split[0]] = urldecode($split[1]);
        }
      }
    }
    $postfields = $this->getPostfields();
    if (!is_null($postfields)) {
      foreach ($postfields as $key => $value) {
        $oauth[$key] = $value;
      }
    }
    $base_info = $this->buildBaseString($url, $requestMethod, $oauth);
    $composite_key = rawurlencode($consumer_secret) . '&' . rawurlencode($oauth_access_token_secret);
    $oauth_signature = base64_encode(hash_hmac('sha1', $base_info, $composite_key, TRUE));
    $oauth['oauth_signature'] = $oauth_signature;
    $this->url = $url;
    $this->requestMethod = $requestMethod;
    $this->oauth = $oauth;
    return $this;
  }

  /**
   * Perform the actual data retrieval from the API.
   *
   * @param bool $return
   *   If true, return data. This is left in for backward compatibility reasons.
   * @param array $curlOptions
   *   Additional Curl options for this request.
   *
   * @throws \Exception
   *
   * @return string
   *   If $return param is true, returns json data.
   */
  public function performRequest($return = TRUE, array $curlOptions = []) {
    if (!is_bool($return)) {
      $this->loggerFactory->notice('performRequest parameter must be true or false');
    }
    $header = [$this->buildAuthorizationHeader($this->oauth), 'Expect:'];
    $getfield = $this->getGetfield();
    $postfields = $this->getPostfields();
    $options = [
      CURLOPT_HTTPHEADER => $header,
      CURLOPT_HEADER => FALSE,
      CURLOPT_URL => $this->url,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_TIMEOUT => 10,
    ] + $curlOptions;
    if (!is_null($postfields)) {
      $options[CURLOPT_POSTFIELDS] = http_build_query($postfields);
    }
    else {
      if ($getfield !== '') {
        $options[CURLOPT_URL] .= $getfield;
      }
    }
    $feed = curl_init();
    curl_setopt_array($feed, $options);
    $json = curl_exec($feed);
    if (($error = curl_error($feed)) !== '') {
      curl_close($feed);
      throw new \Exception($error);
    }
    curl_close($feed);
    return $json;
  }

  /**
   * Private method to generate the base string used by cURL.
   *
   * @param string $baseURI
   *   Base URI.
   * @param string $method
   *   Method.
   * @param array $params
   *   Params.
   *
   * @return string
   *   Built base string
   */
  private function buildBaseString($baseURI, $method, array $params) {
    $return = [];
    ksort($params);
    foreach ($params as $key => $value) {
      $return[] = rawurlencode($key) . '=' . rawurlencode($value);
    }
    return $method . "&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $return));
  }

  /**
   * Private method to generate authorization header used by cURL.
   *
   * @param array $oauth
   *   Array of oauth data generated by buildOauth()
   *
   * @return string
   *   Header used by cURL for request
   */
  private function buildAuthorizationHeader(array $oauth) {
    $return = 'Authorization: OAuth ';
    $values = [];
    foreach ($oauth as $key => $value) {
      if (in_array($key, [
        'oauth_consumer_key',
        'oauth_nonce',
        'oauth_signature',
        'oauth_signature_method',
        'oauth_timestamp',
        'oauth_token',
        'oauth_version',
      ])) {
        $values[] = "$key=\"" . rawurlencode($value) . "\"";
      }
    }
    $return .= implode(', ', $values);
    return $return;
  }

  /**
   * Helper method to perform our request.
   *
   * @param string $url
   *   Url that is being requested.
   * @param string $method
   *   Which method.
   * @param string $data
   *   Data.
   * @param array $curlOptions
   *   Curl options.
   *
   * @throws \Exception
   *
   * @return string
   *   The json response from the server
   */
  public function request($url, $method = 'get', $data = NULL, array $curlOptions = []) {
    if (strtolower($method) === 'get') {
      $this->setGetfield($data);
    }
    else {
      $this->setPostfields($data);
    }
    return $this->buildOauth($url, $method)->performRequest(TRUE, $curlOptions);
  }

  /**
   * Get the screen_name from profile.
   *
   * @return string
   *   The screen_name.
   */
  public function getScreenName(): string {
    return $this->screenName ?? '';
  }

  /**
   * Get tweets count.
   *
   * @return string
   *   String containing how much tweets should be returned on the request.
   */
  public function getTweetCount(): string {
    return $this->tweetCount;
  }

}
