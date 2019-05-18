<?php

namespace Drupal\brightcove\Plugin\QueueWorker;

use Drupal\brightcove\BrightcoveUtil;
use Drupal\brightcove\Entity\BrightcoveCustomField;
use Drupal\brightcove\Entity\BrightcovePlayer;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes Entity Update Tasks for Client.
 *
 * @QueueWorker(
 *   id = "brightcove_client_queue_worker",
 *   title = @Translation("Brightcove client queue worker."),
 *   cron = { "time" = 30 }
 * )
 */
class BrightcoveClientQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The video page queue object.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $videoPageQueue;

  /**
   * The playlist page queue object.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $playlistPageQueue;

  /**
   * The player queue object.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $playerQueue;

  /**
   * The player delete queue object.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $playerDeleteQueue;

  /**
   * The custom field queue object.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $customFieldQueue;

  /**
   * The custom field delete queue object.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $customFieldDeleteQueue;

  /**
   * Constructs a new BrightcoveClientQueueWorker object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Queue\QueueInterface $video_page_queue
   *   The video page queue object.
   * @param \Drupal\Core\Queue\QueueInterface $playlist_page_queue
   *   The playlist page queue object.
   * @param \Drupal\Core\Queue\QueueInterface $player_queue
   *   The player queue object.
   * @param \Drupal\Core\Queue\QueueInterface $player_delete_queue
   *   The player delete queue object.
   * @param \Drupal\Core\Queue\QueueInterface $custom_field_queue
   *   The custom field queue object.
   * @param \Drupal\Core\Queue\QueueInterface $custom_field_delete_queue
   *   The custom field queue object.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, QueueInterface $video_page_queue, QueueInterface $playlist_page_queue, QueueInterface $player_queue, QueueInterface $player_delete_queue, QueueInterface $custom_field_queue, QueueInterface $custom_field_delete_queue) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->videoPageQueue = $video_page_queue;
    $this->playlistPageQueue = $playlist_page_queue;
    $this->playerQueue = $player_queue;
    $this->playerDeleteQueue = $player_delete_queue;
    $this->customFieldQueue = $custom_field_queue;
    $this->customFieldDeleteQueue = $custom_field_delete_queue;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('queue')->get('brightcove_video_page_queue_worker'),
      $container->get('queue')->get('brightcove_playlist_page_queue_worker'),
      $container->get('queue')->get('brightcove_player_queue_worker'),
      $container->get('queue')->get('brightcove_player_delete_queue_worker'),
      $container->get('queue')->get('brightcove_custom_field_queue_worker'),
      $container->get('queue')->get('brightcove_custom_field_delete_queue_worker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $cms = BrightcoveUtil::getCmsApi($data);
    $items_per_page = 100;

    // Create queue item for each player.
    $pm = BrightcoveUtil::getPmApi($data);
    $player_list = $pm->listPlayers();
    $players = [];
    if (!empty($player_list)) {
      $players = $player_list->getItems() ?: [];
    }
    $player_entities = BrightcovePlayer::getList($data);
    foreach ($players as $player) {
      // Remove existing players from the list.
      unset($player_entities[$player->getId()]);

      // Create queue item.
      $this->playerQueue->createItem([
        'api_client_id' => $data,
        'player' => $player,
      ]);
    }
    // Remove non-existing players.
    foreach (array_keys($player_entities) as $player_id) {
      // Create queue item for deletion.
      $this->playerDeleteQueue->createItem(['player_id' => $player_id]);
    }

    /** @var \Brightcove\Object\CustomFields $video_fields */
    // Create queue item for each custom field.
    $video_fields = $cms->getVideoFields();
    $custom_fields = [];
    foreach ($video_fields->getCustomFields() as $custom_field) {
      $custom_fields[] = $custom_field->getId();
      // Create queue item.
      $this->customFieldQueue->createItem([
        'api_client_id' => $data,
        'custom_field' => $custom_field,
      ]);
    }
    // Collect non-existing custom fields and delete them.
    $custom_field_entities = BrightcoveCustomField::loadMultipleByApiClient($data);
    foreach ($custom_field_entities as $custom_field_entity) {
      if (!in_array($custom_field_entity->getCustomFieldId(), $custom_fields)) {
        $this->customFieldDeleteQueue->createItem($custom_field_entity);
      }
    }

    // Create queue items for each video page.
    $video_count = $cms->countVideos();
    $page = 0;
    while ($page * $items_per_page < $video_count) {
      $this->videoPageQueue->createItem([
        'api_client_id' => $data,
        'page' => $page,
        'items_per_page' => $items_per_page,
      ]);
      $page++;
    }

    // Create queue items for each playlist page.
    $playlist_count = $cms->countPlaylists();
    $page = 0;
    while ($page * $items_per_page < $playlist_count) {
      $this->playlistPageQueue->createItem([
        'api_client_id' => $data,
        'page' => $page,
        'items_per_page' => $items_per_page,
      ]);
      $page++;
    }
  }

}
