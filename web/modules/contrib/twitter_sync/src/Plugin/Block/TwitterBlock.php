<?php

namespace Drupal\twitter_sync\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'TwitterBlock' block.
 *
 * @Block(
 *  id = "twitter_sync_block",
 *  admin_label = @Translation("Twitter Sync block"),
 * )
 */
class TwitterBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * A config service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var Drupal\Core\Entity\Query\QueryFactory
   */
  private $entityQuery;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('entity.query'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Constructor of block.
   *
   * @param array $configuration
   *   Configurations.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Config service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entityQuery
   *   EntityQuery builder.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config, QueryFactory $entityQuery, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config;
    $this->entityQuery = $entityQuery;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Query content type twitter_sync and return latest 3 results.
    $query = $this->entityQuery->get('node');
    $query->condition('type', 'twitter_sync')->pager(3);
    $nids = $query->execute();

    $tweets = [];
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    foreach ($nids as $nid) {
      $node = $nodeStorage->load($nid);
      $tweets[] = $node->field_twitter_sync_status_id->value;
      $screen_name = $node->field_twitter_sync_screen_name->value;
    }

    return [
      '#theme' => 'tweets',
      '#tweets_ids' => $tweets,
      '#screen_name' => $screen_name,
      '#attached' => [
        'library' => ['twitter_sync/widgets'],
      ],
    ];
  }

}
