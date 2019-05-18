<?php

namespace Drupal\jDrupal\Plugin\rest\resource;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Psr\Log\LoggerInterface;

/**
 * A home for REST resources provided by jDrupal.
 *
 * @RestResource(
 *   id = "jdrupal_connect",
 *   label = @Translation("jDrupal Connect"),
 *   uri_paths = {
 *     "canonical" = "/jdrupal/connect"
 *   }
 * )
 */
class jDrupalConnect extends ResourceBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('entity.manager'),
      $container->get('current_user')
    );
  }

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
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    EntityManagerInterface $entity_manager,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
  }

  /*
   * Returns data about the current user that will be useful to JavaScript apps.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the jDrupal Connect result.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function get() {
    $account = \Drupal::currentUser();
    $results = array(
      'uid' => $account->id(),
      'name' => $account->getAccountName(),
      'roles' => $account->getRoles()
    );
    \Drupal::moduleHandler()->alter('jdrupal_connect', $results);
    $response = new ResourceResponse($results);
    $response->addCacheableDependency($account);
    return $response;
    throw new HttpException(t('jDrupal Connect GET Failed!'));
  }

}