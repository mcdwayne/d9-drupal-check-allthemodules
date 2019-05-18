<?php

namespace Drupal\encrypt_content_client\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;

/**
 * Provides a resource for getting and adding encrypted fields.
 *
 * @RestResource(
 *   id = "encrypted_fields_resource",
 *   label = @Translation("Client encrypted fields"),
 *   uri_paths = {
 *     "canonical" = "/client_encryption/encrypted_fields/{entity_type}/{entity_id}",
 *     "https://www.drupal.org/link-relations/create" = "/client_encryption/encrypted_fields"
 *   }
 * )
 */
class EncryptedFieldsResource extends ResourceBase {

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
    $query->fields('encryption_containers', ['id'])
      ->condition("encryption_containers.entity_id", (int) $entity_id)
      ->condition("encryption_containers.entity_type", $entity_type);
    $encryption_container_id = (int) $query->execute()->fetchAssoc()['id'][0];

    $query = \Drupal::database()->select('encrypt_content_client_encrypted_fields', 'encrypted_fields');
    $query->fields('encrypted_fields', ['field_name', 'encrypted_content'])
      ->condition("encrypted_fields.encryption_container_id", $encryption_container_id);
    $result = $query->execute();
    
    $encrypted_fields = [];
    while ($row = $result->fetchAssoc()) {
      $encrypted_fields[$row['field_name']] = $row['encrypted_content'];
    }
    
    return new ResourceResponse($encrypted_fields, 200);
  }

  /**
   * Responds to POST requests.
   *
   * Add encrypted fields to the database.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function post(array $data = NULL) {
    
    if (!$this->currentUser->hasPermission("encrypt content client")) {
      throw new AccessDeniedHttpException();
    }
    
    if (!$data['encrypted_fields'] || !$data['encryption_container_id']) {
      return new ResourceResponse("One of the required fields is missing.", 400);
    }

    foreach ($data['encrypted_fields'] as $field) {
      \Drupal::database()->insert('encrypt_content_client_encrypted_fields')
        ->fields([
          'encryption_container_id',
          'field_name',
          'encrypted_content',
        ])
        ->values([
          $data['encryption_container_id'],
          $field['field_name'],
          $field['encrypted_content'],
        ])
        ->execute();
    }

    return new ResourceResponse(count($data['fields']) . " encrypted fields were added.", 200);
  }

  /**
   * Responds to DELETE requests.
   *
   * Delete encrypted fields from the database.
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

    \Drupal::database()->delete('encrypt_content_client_encrypted_fields')
	    ->condition("entity_id", (int) $data['entity_id'])
      ->condition("entity_type", $data['entity_type'])
	    ->execute();
    
    return new ResourceResponse("Encrypted fields were deleted.", 200);
  }
   
}