<?php

/**
 * @file
 * The requests wrappers for methods: PUT, POST, DELETE, GET.
 */

namespace Drupal\desk_net\Controller;

use Drupal\Core\Controller\ControllerBase;

use Drupal\desk_net\Collection\NoticesCollection;
use Drupal\desk_net\Controller\ActionController;

use GuzzleHttp\Exception\RequestException;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

class RequestsController extends ControllerBase {

  /**
   * Getting the Desk-Net TOKEN.
   *
   * @param string $login
   *   The Desk-Net user login.
   * @param string $password
   *   The Desk-Net user password.
   *
   * @return string
   *   The result getting token from Desk-Net.
   */
  public static function getToken($login, $password) {
    // Building URL.
    $url = ModuleSettings::DN_BASE_URL . '/api/token';
    // Request options.
    $options = [
      'verify' => TRUE,
      'form_params' => [
        'grant_type' => 'client_credentials',
        'client_id' => $login,
        'client_secret' => $password,
      ],
      'headers' => [
        'Content-type' => 'application/x-www-form-urlencoded',
      ],
      'http_errors' => FALSE,
    ];

    try {
      $client = \Drupal::httpClient();

      $response = $client->request('POST', $url, $options);

      $code = $response->getStatusCode();
    }
    catch (RequestException $e) {
      \Drupal::logger('desk_net')->notice($e->getMessage());
    }

    if ($code != 200) {
      ModuleSettings::variableSet('desk_net_token', 'not_valid');

      return FALSE;
    }
    // Update token option.
    $response_data = json_decode($response->getBody()->getContents(), TRUE);
    ModuleSettings::variableSet('desk_net_token', $response_data['access_token']);

    return $response_data['access_token'];
  }

  /**
   * Perform HTTP POST/DELETE request.
   *
   * @param string $http_request
   *   Custom HTTP request.
   * @param array $data
   *   The upload data.
   * @param string $url
   *   Base API url.
   * @param string $type
   *   The Desk-Net API method.
   * @param string $record_id
   *   The ID element.
   *
   * @return string
   *   The additional information about story from Desk-Net.
   */
  public function customRequest($http_request, array $data, $url, $type, $record_id = '') {
    $request_url = $url . "/api/v1_0_1/{$type}/{$record_id}";

    $data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Delete ampersand encode.
    $data = str_replace('&amp;', '&', $data);

    $options = [
      'headers' => [
        'Content-Type' => 'application/json;charset=UTF-8',
        'Content-Length' => strlen($data),
        'Authorization' => 'bearer ' . ModuleSettings::variableGet('desk_net_token'),
      ],
      'body' => $data,
      'http_errors' => FALSE,
    ];

    $response = RequestsController::sendRequest($http_request, $request_url, $options, $data);

    return $response;
  }

  /**
   * Perform HTTP GET request.
   *
   * @param string $url
   *   Base API url.
   * @param string $type
   *   The Desk-Net API method.
   * @param string $record_id
   *   ID element.
   *
   * @return string
   *   The request result.
   */
  public function get($url, $type, $record_id = '') {
    $request_url = $url . "/api/v1_0_1/{$type}/{$record_id}";
    $options = array(
      'headers' => [
        'Authorization' => 'bearer ' . ModuleSettings::variableGet('desk_net_token'),
      ],
      'http_errors' => FALSE,
    );

    $response = RequestsController::sendRequest('GET', $request_url, $options);

    return $response;
  }

  /**
   * Perform send HTTP Request.
   *
   * @param string $http_request
   *   Custom HTTP request.
   * @param string $request_url
   *   The link for request.
   * @param array $options
   *   The params for request.
   * @param string $data
   *   The post data.
   * @param bool $last_request
   *   The attempt send request.
   *
   * @return string
   *   The request result.
   */
  private function sendRequest($http_request, $request_url, array $options, $data = NULL, $last_request = NULL) {

    try {
      $client = \Drupal::httpClient();

      $obj_response = $client->request($http_request, $request_url, $options);

    } catch (RequestException $e) {
      \Drupal::logger('desk_net')->notice($e->getMessage());

      return FALSE;
    }

    $http_code = $obj_response->getStatusCode();

    switch ($http_code) {
      case 401:
        $response = $this->authorization($http_request, $request_url, $options, $data, $last_request);
        break;

      case 400:
        if (empty($obj_response->getBody()->__toString())) {
          return FALSE;
        }
        $response = $this->checkPlatformSchedule($obj_response->getBody()->__toString());
        break;

      case 200:
        $response = $obj_response->getBody()->__toString();
        break;

      default:
        if (empty($obj_response->getBody()->__toString())) {
          return FALSE;
        }

        $data = json_decode($data, TRUE);
        $body_response = json_decode($obj_response->getBody()->__toString(), TRUE);
        $response = $this->checkErrorMessage($body_response, $data['publications'][0]['cms_id']);
    }

    return $response;
  }

  /**
   * Perform check user authorized.
   *
   * @param string $http_request
   *   Custom HTTP request.
   * @param string $request_url
   *   The link for request.
   * @param array $args
   *   The params for request.
   * @param string $data
   *   The post data.
   * @param bool $last_request
   *   The attempt send request.
   *
   * @return string|array
   *   The result getting new token.
   */
  private function authorization($http_request, $request_url, array $args, $data, $last_request) {
    $token = $this->getToken(ModuleSettings::variableGet('desk_net_login'),
      ModuleSettings::variableGet('desk_net_password'));

    if ($token !== FALSE) {
      ModuleSettings::variableSet('desk_net_token', $token);
    }

    $response = 'unauthorized';

    if ($last_request != 'update_token' && $token !== FALSE) {
      $args['headers']['Authorization'] = "bearer $token";
      $response = $this->sendRequest($http_request, $request_url, $args, $data, 'update_token');
    }

    return $response;
  }

  /**
   * Perform check platform schedule in response from request.
   *
   * @param object $response
   *   The request response.
   *
   * @return string|array
   *   The request mark.
   */
  private function checkPlatformSchedule($response) {
    $body_response = json_decode($response, TRUE);

    if (preg_match("/^Publication date doesn't match platform schedule/", $body_response['message'])) {
      $response = 'platform_schedule';
    }
    return $response;
  }

  /**
   * Perform check request Error Message from Desk-Net.
   *
   * @param array $message_content
   *   The list message.
   * @param string $node_id
   *   The node ID in Drupal.
   *
   * @return bool
   *   The result of scanning - error message.
   */
  private function checkErrorMessage(array $message_content, $node_id) {
    if (preg_match('/^Page with id \[\d.*\] was not found/', $message_content['message'])) {
      ActionController::getCategory();
      $node = Node::load($node_id);

      ActionController::createNodeDrupalToDN($node);
    }
    elseif (preg_match('/^User with id \[\d.*\] was not found/', $message_content['message'])) {
      preg_match('/\d.*\d/', $message_content['message'], $desk_net_user_id);
      $node = Node::load($node_id);
      $user = User::load($node->getOwnerId());
      // Getting hash field name.
      $hash_field_name = ModuleSettings::variableGet('desk_net_author_id');

      if (isset($user->get($hash_field_name)->value) && !empty($user->get($hash_field_name)->value)) {
        // If field name with Desk-Net revision data was found.
        if ($hash_field_name != NULL && $user->__isset($hash_field_name)) {
          $user->set($hash_field_name, NULL);
          $user->save();
        }
      }
      else {
        return 'not_show_new_notice';
      }

      ActionController::createNodeDrupalToDN($node);
    }
    else {
      return 'not_show_new_notice';
    }

    return 'update_module_data';
  }
}
