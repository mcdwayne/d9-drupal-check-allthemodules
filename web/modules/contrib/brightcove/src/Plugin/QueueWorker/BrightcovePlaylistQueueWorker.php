<?php

namespace Drupal\brightcove\Plugin\QueueWorker;

use Drupal\brightcove\Entity\BrightcovePlaylist;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Queue\RequeueException;

/**
 * Processes Entity Update Tasks for Playlist.
 *
 * @QueueWorker(
 *   id = "brightcove_playlist_queue_worker",
 *   title = @Translation("Brightcove playlist queue worker."),
 *   cron = { "time" = 30 }
 * )
 */
class BrightcovePlaylistQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The brightcove_playlist storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $playlistStorage;

  /**
   * The brightcove_video storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $videoStorage;

  /**
   * Constructs a new BrightcovePlaylistQueueWorker object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $playlist_storage
   *   The brightcove_playlist storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $video_storage
   *   The brightcove_video storage.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityStorageInterface $playlist_storage, EntityStorageInterface $video_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->playlistStorage = $playlist_storage;
    $this->videoStorage = $video_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('brightcove_playlist'),
      $container->get('entity_type.manager')->getStorage('brightcove_video')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    /** @var \Brightcove\Object\Playlist $playlist */
    $playlist = $data['playlist'];

    try {
      BrightcovePlaylist::createOrUpdate($playlist, $this->playlistStorage, $this->videoStorage, $data['api_client_id']);
    }
    catch (\Exception $e) {
      throw new RequeueException($e->getMessage(), $e->getCode(), $e);
    }
  }

}
