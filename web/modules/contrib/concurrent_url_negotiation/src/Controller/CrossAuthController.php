<?php

namespace Drupal\concurrent_url_negotiation\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\concurrent_url_negotiation\CrossAuth;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CrossAuthController.
 *
 * Provides AJAX access to the cross authentication service.
 */
class CrossAuthController implements ContainerInjectionInterface {

  /**
   * The cross authentication service.
   *
   * @var \Drupal\concurrent_url_negotiation\CrossAuth
   */
  protected $crossAuth;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * CrossAuthController constructor.
   *
   * @param \Drupal\concurrent_url_negotiation\CrossAuth $crossAuth
   *    The cross authentication service.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *    Used to get the current request.
   */
  public function __construct(CrossAuth $crossAuth, Request $request) {
    $this->crossAuth = $crossAuth;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('concurrent_url_negotiation.cross_auth'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * Generates and returns token on success.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *    The AJAX response.
   */
  public function getToken() {
    if ($token = $this->crossAuth->generateToken()) {
      return new JsonResponse($token);
    }

    return new JsonResponse([
      'message' => 'Failed to generate cross authentication token.',
    ]);
  }

  /**
   * Authenticates user from token provided from a POST request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *    The AJAX response.
   */
  public function authenticate() {
    $args = json_decode($this->request->getContent());

    if ($this->crossAuth->authenticate($args['id'], $args['token'])) {
      return new JsonResponse(['status' => 'success']);
    }

    return new JsonResponse([
      'status' => 'error',
      'message' => 'Failed to authenticate',
    ]);
  }

}
