<?php

namespace Drupal\simple_openid_server\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\user\Entity\User;
use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 * Provides OpenId Connect features.
 */
class OpenIdController extends ControllerBase  {

  /**
   * Token endpoint
   *
   * @param RouteMatchInterface $route_match
   * @param Request $request
   * @return JsonResponse|Response
   */
  public function token(RouteMatchInterface $route_match, Request $request)
  {
    $grant_type = $request->get('grant_type');
    $client_id = $request->get('client_id');
    $client_secret = $request->get('client_secret');
    $redirect_uri = $request->get('redirect_uri');

    try {
      $code = JWT::decode($request->get('code'), 'code_secret', array('HS256'));
    }
    catch(\Exception $e) {
      $code = new \stdClass();
    }

    if ($grant_type !== 'authorization_code' || $client_id !== $this->getClientId() || $client_secret !== $this->getClientSecret() || $redirect_uri !== $code->redirect_uri) {
      return new JsonResponse(array('error' => 'invalid_request'), JsonResponse::HTTP_BAD_REQUEST);
    }

    $access_token = array(
      "sub" => $code->sub,
    );

    /* @var User user */
    $user = User::load($code->sub);

    $id_token = array(
      "email" => $user->getEmail(),
      "iss" => $request->getSchemeAndHttpHost(),
      "sub" => $user->id(),
      "aud" => $client_id,
      "exp" => time() + 3600,
      "iat" => time()
    );
    return new JsonResponse(array('access_token' => JWT::encode($access_token, 'access_token_secret'), 'token_type' => 'Bearer', 'id_token' => JWT::encode($id_token, 'id_token_secret')), JsonResponse::HTTP_OK);
  }

  /**
   * UserInfo endpoint
   *
   * @param RouteMatchInterface $route_match
   * @param Request $request
   * @return JsonResponse|Response
   */
  public function userInfo(RouteMatchInterface $route_match, Request $request) {
    $authorization = $request->headers->get('Authorization');

    try {
      $access_token = JWT::decode(substr($authorization, 7), 'access_token_secret', array('HS256'));
    }
    catch(\Exception $e) {
      // No token or invalid token
    }

    if (!isset($access_token)) {
      return new Response('', Response::HTTP_UNAUTHORIZED);
    }

    /* @var User user */
    $user = User::load($access_token->sub);

    return new JsonResponse(array('sub' => $user->id(), 'email' => $user->getEmail()), JsonResponse::HTTP_OK);
  }

  protected function getClientId() {
    return \Drupal::config('simple_openid_server.settings')->get('client_id');
  }

  protected function getClientSecret() {
    return \Drupal::config('simple_openid_server.settings')->get('client_secret');
  }
}
