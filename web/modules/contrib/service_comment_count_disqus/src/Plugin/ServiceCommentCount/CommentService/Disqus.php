<?php

namespace Drupal\service_comment_count_disqus\Plugin\ServiceCommentCount\CommentService;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\disqus\DisqusCommentManager;
use Drupal\node\NodeInterface;
use Drupal\service_comment_count\Annotation\CommentService;
use Drupal\service_comment_count\CommentServiceBase;
use Drupal\service_comment_count\CommentServiceInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the 'Disqus' comment service.
 *
 * @CommentService(
 *   id = "disqus",
 *   label = @Translation("Disqus"),
 * )
 */
class Disqus extends CommentServiceBase implements CommentServiceInterface {

  /**
   * The API list limit.
   *
   * @var int
   *   The list limit.
   */
  protected $listLimit = 100;

  /**
   * Disqus comment manager object.
   *
   * @var \Drupal\disqus\DisqusCommentManager
   */
  protected $disqusManager;

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
   * @param \Drupal\disqus\DisqusCommentManager $disqusManager
   *   Disqus manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entityTypeManager, Connection $database, Client $httpClient, DisqusCommentManager $disqusManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config_factory, $entityTypeManager, $database, $httpClient);
    $this->disqusManager = $disqusManager;
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
      $container->get('http_client'),
      $container->get('disqus.manager')
    );
  }

  /**
   * @inheritdoc
   */
  public function isValid() {
    // Check, that there was a public/secret configured for API communication.
    $disqusSettings = $this->configFactory->get('disqus.settings');
    if (empty($disqusSettings->get('advanced.disqus_publickey')) || empty($disqusSettings->get('advanced.disqus_secretkey'))) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * @inheritdoc
   */
  public function buildIdentifier(ContentEntityInterface $entity) {
    // Get the disqus field for the given entity.
    $field = $this->disqusManager->getFields($entity->getEntityTypeId());
    $field_name = key($field);

    // Use custom identifier, when set for the given content entity.
    if ($entity->hasField($field_name) && !empty($entity->get($field_name)->identifier)) {
      return $entity->get($field_name)->identifier;
    }
    return $entity->getEntityTypeId() . '/' . $entity->id();
  }

  /**
   * @inheritdoc
   */
  public function fetchCommentCount(ContentEntityInterface $entity) {
    $this->fetchCommentCountMultiple([$entity]);
  }

  /**
   * @inheritdoc
   */
  public function fetchCommentCountMultiple(array $entities) {
    // Extract the entity identifiers.
    $entity_identifiers = array_map(function ($entity) {
      return $this->buildIdentifier($entity);
    }, $entities);

    // Fetch comments form the API.
    $api = disqus_api();
    $forum = \Drupal::config('disqus.settings')->get('disqus_domain');

    $threads = $api->threads->list([
      'forum' => $forum,
      'thread:ident' => $entity_identifiers,
      'limit' => $this->getListLimit(),
    ]);
    $keys = array_keys($entities);

    // Iterate through our results and store the comment counts.
    foreach ($threads as $delta => $thread) {
      $comment_count = $thread->posts;
      $this->storeCommentCount($entities[$keys[$delta]], $comment_count);
    }
  }

  /**
   * @inheritdoc
   */
  public function getNids($offset = NULL, $limit = NULL) {
    $nids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('status', NodeInterface::PUBLISHED)
      ->condition('field_disqus.status', 1)
      ->range($offset, $limit)
      ->sort('created', 'DESC')
      ->execute();
    return $nids;
  }

}
