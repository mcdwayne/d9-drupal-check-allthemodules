<?php

namespace Drupal\service_comment_count;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Comment service plugins.
 */
abstract class CommentServiceBase extends PluginBase implements CommentServiceInterface, ContainerFactoryPluginInterface {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Guzzle client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The API list limit.
   *
   * @var int
   */
  protected $listLimit = NULL;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   * @param \GuzzleHttp\Client $httpClient
   *   Guzzle client.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entityTypeManager, Connection $database, Client $httpClient) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entityTypeManager;
    $this->database = $database;
    $this->httpClient = $httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function isValid() {
    return TRUE;
  }

  /**
   * Returns the API list limit for the given service.
   *
   * @return int
   *   The list limit.
   */
  public function getListLimit() {
    return $this->listLimit;
  }

  /**
   * Stores the comment count for a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity for which the comment count should be stored.
   * @param int $comment_count
   *   The comment count to be stored.
   *
   * @return mixed
   *
   * @throws \Exception
   */
  protected function storeCommentCount(ContentEntityInterface $entity, $comment_count) {
    $query = $this->database->merge('service_comment_count');

    $query->keys([
      'entity_id' => $entity->id(),
      'entity_type' => $entity->getEntityTypeId(),
      'comment_service_id' => $this->getPluginId(),
    ])
      ->fields([
        'entity_id' => $entity->id(),
        'entity_type' => $entity->getEntityTypeId(),
        'comment_service_id' => $this->getPluginId(),
        'comment_count' => $comment_count,
      ])
      ->execute();
  }

  /**
   * Returns the node ids with a given limit.
   *
   * Whenever possible rewrite it for your service to load only the nodes
   * that are using your comment service, e.g. (field-based, bundle based).
   *
   * @param $offset
   * @param $limit
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getNids($offset = NULL, $limit = NULL) {
    $nids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('status', NodeInterface::PUBLISHED)
      ->range($offset, $limit)
      ->sort('created', 'DESC')
      ->execute();
    return $nids;
  }

}
