<?php

/**
 * @file
 * The endpoints for Desk-Net RESTful API.
 */

namespace Drupal\desk_net\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\Entity\Node;
use Drupal\desk_net\DeleteMethods;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for test_api routes.
 */
class APIController extends ControllerBase {

  /**
   * Perform get Status list from Desk-Net.
   *
   * @param Request $request
   *   The status list with platform ID.
   *
   * @return string
   *   The status saving statuses.
   */
  public function saveStatuses(Request $request) {
    $response = new Response();
    // Token validation.
    if (!$this->isTokenValid($request)) {
      return $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
    }
    // This condition checks the `Content-type` and makes sure to
    // decode JSON string from the request body into array.
    if ( 0 === strpos( $request->headers->get( 'Content-Type' ), 'application/json' ) ) {
      $data = json_decode( $request->getContent(), TRUE );
      $request->request->replace( is_array( $data ) ? $data : [] );
    } else {
      return $response->setStatusCode(Response::HTTP_NO_CONTENT);
    }

    $save_statuses_list = ModuleSettings::variableGet('desk_net_list_active_status');

    if (isset($data['platform'])) {
      ModuleSettings::variableSet('desk_net_platform_id', $data['platform']);
    } else {
      return $response->setStatusCode(Response::HTTP_NO_CONTENT);
    }

    $json_request = ModuleSettings::checkTriggersExportStatus($data);

    if (!empty($save_statuses_list)) {
      $drupal_status_list = array('published', 'unpublished');

      DeleteMethods::shapeDeletedItems($json_request['activeStatuses'],
        $save_statuses_list, $drupal_status_list, 'status');
    }
    if (!empty($json_request['activeStatuses'])) {
      ModuleSettings::variableSet('desk_net_list_active_status',
        $json_request['activeStatuses']);
      ModuleSettings::variableSet('desk_net_status_desk_net_to_drupal_5', 1);
      ModuleSettings::variableSet('desk_net_status_drupal_to_desk_net_1', 5);
    }
    if (!empty($json_request['deactivatedStatuses'])) {
      ModuleSettings::variableSet('desk_net_status_deactivate_status_list',
        $json_request['deactivatedStatuses']);
    }

    return $response->setStatusCode(Response::HTTP_OK);
  }

  /**
   * Perform crete article in Drupal from Desk-Net.
   *
   * @param Request $request
   *   The node data.
   *
   * @return string
   *   The result creating article in Drupal from Desk-Net.
   */
  public function createPublication(Request $request) {
    $response = new Response();
    // Token validation.
    if (!$this->isTokenValid($request)) {
      return $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
    }

    if ( 0 === strpos( $request->headers->get( 'Content-Type' ), 'application/json' ) ) {
      $data = json_decode( $request->getContent(), TRUE );
      $request->request->replace( is_array( $data ) ? $data : [] );
    }

    if (empty($data)) {
      return FALSE;
    }

    $response = ActionController::createNode($data);

    return new JsonResponse($response);
  }

  /**
   * Perform update article in Drupal from Desk-Net.
   *
   * @param RouteMatchInterface $route_match
   *   The node id which should updating.
   * @param Request $request
   *   The node data.
   *
   * @return string
   *   The result updating article in Drupal from Desk-Net.
   */
  public function updatePublication(RouteMatchInterface $route_match, Request $request) {
    $response = new Response();
    // Token validation.
    if (!$this->isTokenValid($request)) {
      return $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
    }

    $story_id = $route_match->getRawParameter('story_id');

    if ( 0 === strpos( $request->headers->get( 'Content-Type' ), 'application/json' ) ) {
      $data = json_decode( $request->getContent(), TRUE );
      $request->request->replace( is_array( $data ) ? $data : [] );
    }

    if (empty($data) || empty($story_id) || !is_numeric($story_id)) {
      return $response->setStatusCode(Response::HTTP_ACCEPTED);
    }

    // Loading node by id.
    $node = Node::load($story_id);
    if ($node !== NULL) {

      // Skipping updating slug if node was published.
      if ($node->status->value == 1 && isset($data['slug'])) {
        unset($data['slug']);
      }

      // Getting Desk-Net revision data.
      $desk_net_revision = ModuleSettings::deskNetRevisionGet($node);
      // Check node - removed status.
      if ($desk_net_revision !== FALSE && $desk_net_revision['desk_net_removed_status'] == 'desk_net_removed') {
        return $response->setStatusCode(Response::HTTP_ACCEPTED);
      }
    } else {
      return $response->setStatusCode(Response::HTTP_NOT_FOUND);
    }

    $response = ActionController::updateNode($data, $story_id, $desk_net_revision);

    return new JsonResponse($response);
  }

  /**
   * Perform delete node in Drupal by Desk-Net.
   *
   * @param RouteMatchInterface $route_match
   *   The node id for delete.
   * @param Request $request
   *   The request object.
   *
   * @return array|bool
   *   The result updating article status on 'Deleted/Removed' in Drupal.
   */
  public function deletePublication(RouteMatchInterface $route_match, Request $request) {
    $response = new Response();
    // Token validation.
    if (!$this->isTokenValid($request)) {
      return $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
    }

    // Getting cms_id.
    $node_id = $route_match->getRawParameter('story_id');

    // Getting value for matching "Deleted/Removed" on page "Status Matching".
    $matching_status_remove = ModuleSettings::variableGet('desk_net_status_desk_net_to_drupal_desk_net_removed');

    if ($matching_status_remove === NULL) {
      $matching_status_remove = 0;
    }
    // Loading node by id.
    $node = Node::load($node_id);

    if ($node == NULL) {
      return $response->setStatusCode(Response::HTTP_NOT_FOUND);
    }
    // Getting Desk-Net revision data.
    $desk_net_revision = ModuleSettings::deskNetRevisionGet($node);
    // Adding information for the node about deleting story in Desk-Net app.
    $desk_net_revision['desk_net_removed_status'] = 'desk_net_removed';
    $node = ModuleSettings::deskNetRevisionSet($node, $desk_net_revision);
    // Updating node status.
    $node->set('status', $matching_status_remove);

    // Saving changed.
    $node->save();


    return $response->setStatusCode(Response::HTTP_OK);
  }

  /**
   * Validating authorization Token.
   *
   * @param object $request
   *   The token.
   *
   * @return boolean
   *   The result of checking.
   */
  protected function isTokenValid($request) {
    try {
      if (!empty($request->headers->get('authorization'))) {
        $token = APIController::getInfoToken($request->headers->get('authorization'), 'token');
      }

      // Determine if $token is empty.
      if (empty($token)) {
        throw new \InvalidArgumentException("The client has not transmitted the token in the request.");
      }
      // Retrieve access token data.
      $info = $this->getAccessToken($token);

      if (empty($info)) {
        throw new \InvalidArgumentException("The token: " . $token . " provided is not registered.");
      }

      // Determine if $info['server'] is empty.
      if (empty($info['server'])) {
        throw new \Exception("OAuth2 server was not set");
      }
      // Set $oauth2_server_name.
      $oauth2_server_name = 'oauth2_server.server.' . $info['server'];
      // Retrieves the configuration object.
      $config = \Drupal::config($oauth2_server_name);

      // Determine if $config is empty.
      if (empty($config)) {
        throw new \Exception("The config for '.$oauth2_server_name.' server could not be loaded.");
      }
      $oauth2_server_settings = $config->get('settings');
      if (empty($oauth2_server_settings['advanced_settings']) || empty($oauth2_server_settings['advanced_settings']['access_lifetime'])) {
        throw new \Exception("The access_lifetime was not set.");
      }
      if (REQUEST_TIME > ($info['expires'] + $oauth2_server_settings['advanced_settings']['access_lifetime'])) {
        throw new \Exception("The token is expired.");
      } else {
        return TRUE;
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('access denied')->warning($e->getMessage());
      return FALSE;
    }
  }

  /**
   * Get access token.
   *
   * @param string $access_token
   *   The token.
   *
   * @return array|boolean
   *   The token info.
   */
  public function getAccessToken($access_token) {
    $token = $this->getStorageToken($access_token);

    if (!$token || $token->getClient() == FALSE) {
      return FALSE;
    }

    $scopes = [];
    $scope_entities = $token->scopes->referencedEntities();
    foreach ($scope_entities as $scope) {
      $scopes[] = $scope->scope_id;
    }
    sort($scopes);

    // Return a token array in the format expected by the library.
    $token_array = [
      'server' => $token->getClient()->getServer()->id(),
      'client_id' => $token->getClient()->client_id,
      'access_token' => $token->token->value,
      'expires' => (int) $token->expires->value,
      'scope' => implode(' ', $scopes),
    ];

    return $token_array;
  }

  /**
   * Get the token from the entity backend.
   *
   * @param string $token
   *   The token to find.
   *
   * @return object|bool
   *   Returns the token or FALSE.
   */
  public function getStorageToken($token) {
    $tokens = \Drupal::entityTypeManager()->getStorage('oauth2_server_token')->loadByProperties(['token' => "$token"]);

    if ($tokens) {
      return reset($tokens);
    }
    return FALSE;
  }

  /**
   * Generates keys from "Authorization" request header field.
   *
   * @param string $authorization
   *   The "Authorization" request header field.
   * @param string $key
   *   Token / authentication_scheme.
   *
   * @return array|false
   *   An array with the following keys:
   *   - authentication_scheme: (string) HTTP Authentication Scheme.
   *   - token: (string) $token.
   */
  protected static function getInfoToken($authorization = NULL, $key = NULL) {

    if (empty($authorization)) {
      return FALSE;
    }

    @list($authentication_scheme, $token) = explode(' ', $authorization, 2);
    if (empty($token)) {
      return FALSE;
    }
    $infoToken = [
      'authentication_scheme' => $authentication_scheme,
      'token' => $token,
    ];
    if (!empty($key) && array_key_exists($key, $infoToken)) {
      return $infoToken[$key];
    }
    else {
      return $infoToken;
    }
  }
}
