<?php

namespace Drupal\reactify_auth\Plugin\rest\resource;

use Drupal\jwt\Transcoder\JwtTranscoder;
use Drupal\jwt\Authentication\Event\JwtAuthGenerateEvent;
use Drupal\jwt\Authentication\Event\JwtAuthEvents;
use Drupal\jwt\JsonWebToken\JsonWebToken;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Drupal\rest\ResourceResponse;
use Drupal\user\Entity\User;
use Firebase\JWT\JWT;

/**
 * Provides a resource for getting JWT token.
 *
 * @RestResource(
 *   id = "jwt_token_resource",
 *   label = @Translation("JWT token resource"),
 *   uri_paths = {
 *     "canonical" = "/api/token",
 *     "https://www.drupal.org/link-relations/create" = "/api/token"
 *   }
 * )
 */
class TokenResource extends ResourceBase {
  protected $transcoder;

  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    $serializer_formats,
    LoggerInterface $logger
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->eventDispatcher = \Drupal::service('event_dispatcher');
    $this->transcoder = new JwtTranscoder(new JWT(), \Drupal::configFactory(), \Drupal::service('key.repository'));
  }

  /**
   * Responds to POST request.
   *
   * @return string
   *   User info in json format.
   */
  public function post() {
    if (\Drupal::currentUser()->isAnonymous()) {
      $data['message'] = $this->t('Login failed.');
      return new ResourceResponse($data, 403);
    }
    else {
      $data['id'] = \Drupal::currentUser()->id();
      $data['message'] = $this->t('Login succeeded');
      $data['token'] = $this->generateToken();
      $data['roles'] = $this->getRoles(\Drupal::currentUser()->id());
      return new ResourceResponse($data);
    }
  }

  /**
   * Get roles of user at login.
   *
   * @param string $userId
   *   Id of the user.
   *
   * @return array
   *   Returns user roles.
   */
  private function getRoles($userId) {
    $user = User::load($userId);
    $roles = $user->getRoles();
    return $roles;
  }

  /**
   * Generate JWT token.
   *
   * @return string
   *   Returns jwt token.
   */
  public function generateToken() {
    $event = new JwtAuthGenerateEvent(new JsonWebToken());
    $this->eventDispatcher->dispatch(JwtAuthEvents::GENERATE, $event);
    $jwt = $event->getToken();
    return $this->transcoder->encode($jwt);
  }

}
