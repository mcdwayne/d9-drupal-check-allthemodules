<?php

namespace Drupal\encrypt_content_client\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Drupal\user\Entity\User;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "ecc_key_resource",
 *   label = @Translation("ECC keys"),
 *   uri_paths = {
 *     "canonical" = "/client_encryption/keys/{uid}",
 *     "https://www.drupal.org/link-relations/create" = "/client_encryption/keys/update"
 *   }
 * )
 */
class EccKeyResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('encrypt_content_client'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to GET requests.
   *
   * Returns a list of public ECC keys of all or an user.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get($uid = NULL) {
    
    if (!$this->currentUser->hasPermission("encrypt content client")) {
      throw new AccessDeniedHttpException();
    }
    
    if ($uid == "all") {

      $query = \Drupal::entityQuery('user');
      $ids = $query->execute();
      $users = User::loadMultiple($ids);
      $keys = [];

      // Ignore first user entry where uid = 0.
      foreach ($users as $user) {
        if ($user->id() > 0) {
          $keys[$user->id()] = $user->field_public_key->value;
        }
      }

      if (empty($keys)) {
        return new ResourceResponse("No ECC keys have been found.", 400);
      }

      return new ResourceResponse(json_encode($keys), 200);
    }
    else {
      // Return only key for a single user.
      $user = User::load($uid);
      $key = $user->field_public_key->value;

      return new ResourceResponse($key);
    }

  }

  /**
   * Responds to POST requests.
   *
   * Update private/public ECC key of an user.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function post(array $data = NULL) {
    
    if (!$this->currentUser->hasPermission("encrypt content client")) {
      throw new AccessDeniedHttpException();
    }
    
    if ($data['public_key']) {
      $user_id = $this->currentUser->id();
      $user = User::load($user_id);
      
      $user->set("field_public_key", $data['public_key']);
      $user->save();

      return new ResourceResponse('Public key has been updated.', 200);
    } 
    else {
      return new ResourceResponse('Public key must be provided.', 400);
    }
  }

  /**
   * Responds to DELETE requests.
   *
   * Delete current user's public key.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function delete() {
    
    if (!$this->currentUser->hasPermission("encrypt content client")) {
      throw new AccessDeniedHttpException();
    }
    
    $user_id = $this->currentUser->id();
    $user = User::load($user_id);
    
    $user->set("field_public_key", NULL);
    $user->save();
    
    return new ResourceResponse("Public key has been deleted.", 200);
  }

}
