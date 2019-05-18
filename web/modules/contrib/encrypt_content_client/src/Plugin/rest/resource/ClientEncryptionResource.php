<?php

namespace Drupal\encrypt_content_client\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "client_encryption_resource",
 *   label = @Translation("Client encrypted containers"),
 *   uri_paths = {
 *     "canonical" = "/client_encryption/encryption_container/{entity_type}/{entity_id}",
 *     "https://www.drupal.org/link-relations/create" = "/client_encryption/encryption_container"
 *   }
 * )
 */
class ClientEncryptionResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new ClientEncryptionRestResource object.
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
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get($entity_type = NULL, $entity_id = NULL) {
    
    if (!$this->currentUser->hasPermission("encrypt content client")) {
      throw new AccessDeniedHttpException();
    }

    if (!$entity_type || !$entity_id) {
      return new ResourceResponse("One of the required fields is missing.", 400);
    }
    
    $query = \Drupal::database()->select('encrypt_content_client_encryption_containers', 'encryption_containers');
    $query->fields('encryption_containers', ['encrypted_data_keys'])
      ->condition("encryption_containers.entity_id", (int) $entity_id)
      ->condition("encryption_containers.entity_type", $entity_type);
    $encrypted_data_keys = $query->execute()->fetchAssoc()['encrypted_data_keys'];
    
    return new ResourceResponse($encrypted_data_keys, 200);
  }

  /**
   * Responds to POST requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function post(array $data = NULL) {
    
    if (!$this->currentUser->hasPermission("encrypt content client")) {
      throw new AccessDeniedHttpException();
    }
    
    if (!$data['entity_id'] || !$data['entity_type'] || !$data['encrypted_data_keys']) {
      return new ResourceResponse("One of the required fields is missing.", 400);
    }
    
    // Check if an encryption container already exists.
    $query = \Drupal::database()->select('encrypt_content_client_encryption_containers', 'encryption_containers');
    $query->fields('encryption_containers', ['encrypted_data_keys'])
      ->condition("encryption_containers.entity_id", (int) $data['entity_id'])
      ->condition("encryption_containers.entity_type", $data['entity_type']);
    $encryption_container_id = (int) $query->execute()->fetchAssoc()['id'][0];
    
   if ($encryption_container_id) {
     \Drupal::database()->update('encrypt_content_client_encryption_containers')
	     ->condition("id", $encryption_container_id)
	     ->fields([
		     'encrypted_data_keys' => $data['encrypted_data_keys'],
	     ])
	     ->execute();
	     
     return new ResourceResponse($encryption_container_id, 200);
   } 
   else {
     // Encryption container does not exist, create a new one.
     $encryption_container_id = \Drupal::database()
        ->insert('encrypt_content_client_encryption_containers')
        ->fields([
          'entity_id',
          'entity_type',
          'encrypted_data_keys',
        ])
        ->values([
          $data['entity_id'],
          $data['entity_type'],
          $data['encrypted_data_keys'],
        ])
        ->execute();
        
     return new ResourceResponse($encryption_container_id, 200);
   }
  }
  
  /**
   * Responds to DELETE requests.
   *
   * Delete encryption container from the database.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function delete(array $data = NULL) {
    
    if (!$this->currentUser->hasPermission("encrypt content client")) {
      throw new AccessDeniedHttpException();
    }
    
    if (!$data['entity_id'] || !$data['entity_type']) {
      return new ResourceResponse("One of the required fields is missing.", 400);
    }

    \Drupal::database()->delete('encrypt_content_client_encryption_containers')
	    ->condition("entity_id", (int) $data['entity_id'])
      ->condition("entity_type", $data['entity_type'])
	    ->execute();
    
    return new ResourceResponse("Encrypted fields were deleted.", 200);
  }

}
