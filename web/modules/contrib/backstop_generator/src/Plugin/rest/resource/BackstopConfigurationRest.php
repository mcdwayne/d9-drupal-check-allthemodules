<?php

namespace Drupal\backstop_generator\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\backstop_generator\BackstopConfigRetrieverService;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "backstop_configuration_rest",
 *   label = @Translation("BackstopJS Configuration REST Export"),
 *   uri_paths = {
 *     "canonical" = "/admin/backstop_generator/backstop_configuration/export"
 *   }
 * )
 */
class BackstopConfigurationRest extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;
  protected $backstopConfigRetrieverService;

  /**
   * Constructs a new BackstopConfigurationRest object.
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
   * @param \Drupal\backstop_generator\BackstopConfigRetrieverService $backstopConfigRetrieverService
   *   The config fetcher service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    BackstopConfigRetrieverService $backstopConfigRetrieverService) {
    $this->backstopConfigRetrieverService = $backstopConfigRetrieverService;
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
      $container->get('logger.factory')->get('backstop_generator'),
      $container->get('current_user'),
      $container->get('backstop_generator.config_retriever')
    );
  }

  /**
   * Responds to GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get() {

    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }
    $entity = $this->backstopConfigRetrieverService->getConfigurationObject();
    // We don't want to cache cache, so we need modifiedresourceresponse.
    return new ModifiedResourceResponse($entity, 200);
  }

}
