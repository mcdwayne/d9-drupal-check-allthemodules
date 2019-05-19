<?php
/**
 * @file
 * Contains \Drupal\webtexttool\Controller\WebtexttoolController.
 */

namespace Drupal\webtexttool\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Url;

/**
 * Class WebtexttoolController
 *
 * @package Drupal\webtexttool\Controller
 */
class WebtexttoolController extends ControllerBase {

  /**
   * Base url of the webtexttool API.
   */
  const WEBTEXTTOOL_URL = 'https://api.webtexttool.com';

  /**
   * The temp store factory.
   */
  protected $tempStoreFactory;

  /**
   * Helper function to check if user is authenticated.
   */
  public function webtexttoolIsAuthenticated() {
    $is_authenticated = $this->webtexttoolConnect('/user/authenticated');
    return $is_authenticated;
  }
  /**
   * Implements searchkeyword from api.
   */
  public function webtexttoolSearchKeyword($keyword, $language = 'us') {
    $response = $this->webtexttoolConnect('/keyword/searchkeyword/' . $keyword . '/' . $language);
    return $response;
  }

  /**
   * Implements GetSuggestions from api.
   */
  public function webtexttoolAnalyse($html, $keywords, $language = 'en') {

    // Set default paramaters
    $parameters = array(
      'content' => $html,
      'keywords' => $keywords,
      'languageCode' => 'en',
    );
    $response = $this->webtexttoolConnect('/page/suggestions',$this->webtexttoolGetToken(), 'post', $parameters);
    return $response;
  }

  /**
   *
   * Connects to the webtexttool API.
   *
   * @param $function
   * @param string $token
   * @param string $method
   * @param array $parameters
   *
   * @return bool|mixed|string
   */
  protected function webtexttoolConnect($function, $token = '',$method = 'get', $parameters = array()) {

    // If we don't have a token, get one.
    if($token == '') {
      $token = $this->webtexttoolLogin();
    }
    try {
      // Construct headers and client.
      $headers = array('Content-Type' => 'application/json', 'WttSource' => 'Drupal', 'Authorization' => 'bearer ' . $token);
      $client = new Client([
        'base_uri' => SELF::WEBTEXTTOOL_URL,
        'headers' => $headers
      ]);

      // Post handling.
      if($method == 'post'){
        $request = $client->post($function, [
          'body' => json_encode($parameters),
        ]);
      }
      // Get handling.
      else{
        $request = $client->get($function);
      }

      // Get the response body of the request.
      $data = (string) $request->getBody();

      // Return if the reponse is empty.
      if (empty($data)) {
        return FALSE;
      }

      // Return the result of the request.
      else{
        $data = json_decode($data);
        if(!empty($data)) {
          return $data;
        }
        else{
          return false;
        }
      }
    }
    // Error handling.
    catch (RequestException $e) {
      drupal_set_message(t('Unable to connect to the webtexttool service please check your credentials and try again later.'), 'error');
      return FALSE;
    }

    return false;
  }

  /**
   * Register to webtexttool api service.
   *
   */
  public function webtexttoolRegister($email, $pass, $language) {

    // Construct headers and request body.
    $headers = array('Content-Type' => 'application/json', 'WttSource' => 'Drupal');
    $body = array(
      'Language' => $language,
      'UserName' => $email,
      'Password' => $pass,
    );
    try {

      // Construct client.
      $client = new Client([
        'base_uri' => SELF::WEBTEXTTOOL_URL,
      ]);

      // Create the request.
      $request = $client->post('/user/register', [
        'headers' => $headers,
        'body' => json_encode($body),
      ]);

      // Get the response body.
      $data = (string) $request->getBody();

      // Jump ship if we don't have a response body.
      if (empty($data)) {
        return FALSE;
      }
      else {

        // Decode the response body.
        $data = json_decode($data);
        $token = $data->access_token;

        // If we received an access token, save it.
        if(!empty($token)){
          $this->webtexttoolSetToken($token);
          return $token;
        }
        else{
          return false;
        }
      }
    }
    // Error handling.
    catch (RequestException $e) {
      return FALSE;
    }
  }

  /**
   * Logs in to webtexttool api service.
   *
   */
  protected function webtexttoolLogin() {

    // Create headers and response body.
    $headers = array('Content-Type' => 'application/json', 'WttSource' => 'Drupal');
    $body = array(
      'Language' => \Drupal::config('webtexttool.settings')->get('language'),
      'RememberMe' => TRUE,
      'UserName' => \Drupal::config('webtexttool.settings')->get('user'),
      'Password' => \Drupal::config('webtexttool.settings')->get('pass'),
    );
    try {

      // Construct client and request.
      $client = new Client([
        'base_uri' => SELF::WEBTEXTTOOL_URL,
      ]);

      $request = $client->post("/user/login", [
        'headers' => $headers,
        'body' => json_encode($body),
        'timeout' => 60,
      ]);

      // Check if we have a response body.
      $data = (string) $request->getBody();
      if (empty($data)) {
        return FALSE;
      }
      else{

        // Decode the response.
        $data = json_decode($data);
        $token = $data->access_token;

        // If we received an access token, save it.
        if(!empty($token)){
          $this->webtexttoolSetToken($token);
          return $token;
        }

        // Give feedback about the failed login.
        else {
          drupal_set_message(t('Unable to login to the webtexttool service please check your credentials and try again later.'), 'error');
          return false;
        }
      }
    }
    // Error handling.
    catch (RequestException $e) {
      drupal_set_message(t('Unable to login to the webtexttool service please check your credentials and try again later.'), 'error');
      return FALSE;
    }
  }

  /**
   * Generate the correct headers with bearer.
   *
   */
  protected function webtexttoolHeaders($token = '') {

    // Set up headers.
    $headers = array('Content-Type' => 'application/json', 'WttSource' => 'Drupal');

    // Get the token
    $token = $this->webtexttoolGetToken($token);

    // Set up authorization header.
    if($token && $token !== ''){
      $headers['Authorization'] = 'bearer ' . $token;
    }

    // Return the headers.
    return $headers;
  }

  /**
   * Set token from webtexttool.
   */
  public function webtexttoolSetToken($token) {

    // Set the received token in the temporary storage.
    $tempstore = \Drupal::service('user.private_tempstore')->get('webtexttool');
    $tempstore->set('webtexttooltoken', $token);
  }

  /**
   * Get token from webtexttool.
   */
  protected function webtexttoolGetToken($token = '') {

    // Jump ship if we already have a token.
    if($token !== ''){
      return $token;
    }

    // Get the token form the temporary storage.
    $tempstore = \Drupal::service('user.private_tempstore')->get('webtexttool');
    $token = $tempstore->get('webtexttooltoken');

    // If we don't have a token in the temporary storage, get a new one.
    if(empty($token)) {
      $token = $this->webtexttoolLogin();
    }

    // Return the token.
    return $token;
  }

  /**
   * Implements getkeywordsources from api.
   */
  public function webtexttoolGetSources() {

    $keywords = array();

    // Get the keyword sources from the cache.
    if ($cache = \Drupal::cache()->get('webtexttool_getkeywordsources') ) {
      $keywords = $cache->data;
    }
    // Get the list of languages supported.
    else {
      $keywords_languages = $this->webtexttoolConnect('/keywords/sources');

      if ($keywords_languages) {
        foreach ($keywords_languages as $keyword) {
          $keywords[$keyword->Value] = $keyword->Name;
        }

        // Set the keyword sources in the cache.
        $set_cache = \Drupal::cache()->set('webtexttool_getkeywordsources', $keywords);
      }
    }

    // Return the keywords.
    return $keywords;
  }

  /**
   * Page callback for the account status page.
   */
  public function accountStatus() {

    // Check if the user is authenticated.
    $is_authenticated = $this->webtexttoolIsAuthenticated();

    // If not, try to login the user.
    if (!$is_authenticated) {
      $this->webtexttoolLogin();
    }

    // Return the markup of the account.
    $render = array(
      '#markup' => $this->webtexttoolAccount(),
    );

    return $render;
  }


  /**
   * Get an account.
   */
  public function webtexttoolAccount() {

    // Get the current account.
    $account = $this->webtexttoolConnect('/user/info', $this->webtexttoolGetToken());

    if ($account) {

      // Create a render array.
      $render = array(
        '#prefix' => '<div class="webtexttool-account">',
        'account' => array(
          '#markup' => 'Username: ' . $account->UserName . ' FullName: ' . $account->FullName . ' Subscription: ' . $account->SubscriptionName,
        ),
        '#suffix' => '</div>'
      );

      return render($render);
    }

    // Give feedback that the user is not currently logged in.
    $url = Url::fromRoute('system.admin_config_webtexttool.settings');
    return $this->t('Unable to load your account. Please check and update your credentials at @url.', array('@url' => $this->l($this->t('My account'), $url)));
  }
}