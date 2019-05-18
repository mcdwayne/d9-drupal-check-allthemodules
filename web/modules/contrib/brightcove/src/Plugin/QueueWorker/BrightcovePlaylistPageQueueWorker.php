<?php

namespace Drupal\brightcove\Plugin\QueueWorker;

use Drupal\brightcove\BrightcoveUtil;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes Entity Update Tasks for Playlist.
 *
 * @QueueWorker(
 *   id = "brightcove_playlist_page_queue_worker",
 *   title = @Translation("Brightcove client queue worker."),
 *   cron = { "time" = 30 }
 * )
 */
class BrightcovePlaylistPageQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The playlist queue object.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $playlistQueue;

  /**
   * Constructs a new BrightcovePlaylistPageQueueWorker object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Queue\QueueInterface $playlist_queue
   *   The queue object.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, QueueInterface $playlist_queue) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->playlistQueue = $playlist_queue;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('queue')->get('brightcove_playlist_queue_worker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $cms = BrightcoveUtil::getCmsApi($data['api_client_id']);

    // Get playlists.
    $playlists = $cms->listPlaylists(NULL, $data['items_per_page'], $data['page'] * $data['items_per_page']);
    foreach ($playlists as $playlist) {
      $this->playlistQueue->createItem([
        'api_client_id' => $data['api_client_id'],
        'playlist' => $playlist,
      ]);
    }
  }

}
