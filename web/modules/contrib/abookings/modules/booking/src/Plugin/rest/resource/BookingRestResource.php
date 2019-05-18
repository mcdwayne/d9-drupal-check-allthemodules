<?php

namespace Drupal\booking\Plugin\rest\resource;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rest\ModifiedResourceResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

// use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\rest\resource\EntityResource;

// OLD serialization_class = "Drupal\Core\Entity\Entity"

/**
 * A resource for viewing, editing, and deleting a booking.
 *
 * @RestResource(
 *   id = "booking_rest_resource",
 *   label = @Translation("Booking"),
 *   serialization_class = "Drupal\Core\Entity\ContentEntityType",
 *   uri_paths = {
 *     "canonical" = "/bookings/booking/{booking}"
 *   }
 * )
 */
class BookingRestResource extends EntityResource {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, $serializer_formats, LoggerInterface $logger, ConfigFactoryInterface $config_factory) {
    
    if (! $plugin_definition['entity_type']) {
      $plugin_definition['entity_type'] = 'node';
    }

    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $serializer_formats, $logger, $config_factory);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return parent::create($container, $configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function get($entity) {
    $entity = (gettype($entity) === 'string') ? node_load($entity) : $entity;
    return parent::get($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function post($entity = NULL) {
    // kint($entity, '$entity');
    $entity = (gettype($entity) === 'string') ? node_load($entity) : $entity;
    return parent::post($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($entity) {
    $entity = (gettype($entity) === 'string') ? node_load($entity) : $entity;
    return parent::delete($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function patch($original_entity, $entity = NULL) {
    $entity = (gettype($entity) === 'string') ? node_load($entity) : $entity;
    return parent::patch($original_entity, $entity);
  }

}
