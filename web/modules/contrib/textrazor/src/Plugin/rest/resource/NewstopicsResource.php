<?php
/**
 * @file
 * Implements a REST API for newscode integration in the frontend.
 *
 * Contains a internal getter to retrieve terms labels based on the newscode field value.
 * Used for the integration with the TextRazor fields on node edit form.
 */

namespace Drupal\textrazor\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;

/**
 * Provides Newstopics label getter.
 *
 * @RestResource(
 *   id = "newstopics_connector",
 *   label = @Translation("Newstopics connector"),
 *   serialization_class = "",
 *   uri_paths = {
 *     "canonical" = "/newstopics/{newscode}",
 *     "https://www.drupal.org/link-relations/create" = "/newstopics"
 *   }
 * )
 *
 */
class NewstopicsResource extends ResourceBase {

  // Field to look into to retrieve the terms.
  const FIELD_NAME = 'field_newscode';

  /**
   * Stores the entityType Manager.
   */
  protected $entityTypeManager;

  public function __construct(array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    EntityTypeManagerInterface $entity_type_manager) {
      parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
      $this->entityTypeManager = $entity_type_manager;
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
      $container->get('logger.factory')->get('textrazor'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Responds to entity GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function get($newscode) {
    $terms_list = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([static::FIELD_NAME => [$newscode]]);
    $name = reset($terms_list)->getName();
    $response = [ 'term_name' => $name ];
    return new ResourceResponse($response);
  }

}
