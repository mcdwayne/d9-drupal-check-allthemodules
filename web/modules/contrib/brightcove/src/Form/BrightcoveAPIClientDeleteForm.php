<?php

namespace Drupal\brightcove\Form;

use Drupal\brightcove\BrightcoveUtil;
use Drupal\brightcove\Entity\BrightcoveSubscription;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete Brightcove API Client entities.
 */
class BrightcoveAPIClientDeleteForm extends EntityConfirmFormBase {

  /**
   * Query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The playlist local delete queue object.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $playlistLocalDeleteQueue;

  /**
   * The video local delete queue object.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $videoLocalDeleteQueue;

  /**
   * The player local delete queue object.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $playerDeleteQueue;

  /**
   * The custom field local delete queue object.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $customFieldDeleteQueue;

  /**
   * The playlist page queue object.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $textTrackDeleteQueue;

  /**
   * The subscription delete queue object.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $subscriptionDeleteQueue;

  /**
   * Constructs a new BrightcoveAPIClientDeleteForm.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   Query factory.
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection.
   * @param \Drupal\Core\Queue\QueueInterface $playlist_local_delete_queue
   *   Playlist local delete queue worker.
   * @param \Drupal\Core\Queue\QueueInterface $video_local_delete_queue
   *   Video local delete queue worker.
   * @param \Drupal\Core\Queue\QueueInterface $player_delete_queue
   *   Player local delete queue worker.
   * @param \Drupal\Core\Queue\QueueInterface $custom_field_delete_queue
   *   Custom field local delete queue worker.
   * @param \Drupal\Core\Queue\QueueInterface $text_track_delete_queue
   *   Text track delete queue object.
   * @param \Drupal\Core\Queue\QueueInterface $subscription_delete_queue
   *   Subscription delete queue object.
   */
  public function __construct(QueryFactory $query_factory, Connection $connection, QueueInterface $playlist_local_delete_queue, QueueInterface $video_local_delete_queue, QueueInterface $player_delete_queue, QueueInterface $custom_field_delete_queue, QueueInterface $text_track_delete_queue, QueueInterface $subscription_delete_queue) {
    $this->queryFactory = $query_factory;
    $this->connection = $connection;
    $this->playlistLocalDeleteQueue = $playlist_local_delete_queue;
    $this->videoLocalDeleteQueue = $video_local_delete_queue;
    $this->playerDeleteQueue = $player_delete_queue;
    $this->customFieldDeleteQueue = $custom_field_delete_queue;
    $this->textTrackDeleteQueue = $text_track_delete_queue;
    $this->subscriptionDeleteQueue = $subscription_delete_queue;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('database'),
      $container->get('queue')->get('brightcove_playlist_local_delete_queue_worker'),
      $container->get('queue')->get('brightcove_video_local_delete_queue_worker'),
      $container->get('queue')->get('brightcove_player_delete_queue_worker'),
      $container->get('queue')->get('brightcove_custom_field_delete_queue_worker'),
      $container->get('queue')->get('brightcove_text_track_delete_queue_worker'),
      $container->get('queue')->get('brightcove_subscription_delete_queue_worker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return parent::getDescription() . '<br>' . $this->t('Warning: By deleting API Client all of its local contents will be deleted too, including videos, playlists, players, custom fields and subscriptions.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.brightcove_api_client.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\brightcove\Entity\BrightcoveAPIClient $entity */
    $entity = $this->entity;

    // Empty queues.
    foreach (BrightcoveUtil::getStatusQueues() as $queue) {
      BrightcoveUtil::clearQueue($queue);
    }

    // Collect all playlists belonging for the api client.
    $playlists = $this->queryFactory->get('brightcove_playlist')
      ->condition('api_client', $entity->id())
      ->execute();
    foreach ($playlists as $playlist) {
      $this->playlistLocalDeleteQueue->createItem($playlist);
    }

    // Collect all text tracks belonging for the api client.
    $query = $this->connection->select('brightcove_text_track', 'btt')
      ->fields('btt', ['text_track_id']);
    $query->innerJoin('brightcove_video__text_tracks', 'bvtt', '%alias.text_tracks_target_id = btt.bcttid');
    $query->innerJoin('brightcove_video', 'bv', '%alias.bcvid = bvtt.entity_id');
    $text_tracks = $query->condition('api_client', $entity->id())
      ->execute();
    foreach ($text_tracks as $text_track) {
      $this->textTrackDeleteQueue->createItem($text_track->text_track_id);
    }

    // Collect all videos belonging for the api client.
    $videos = $this->queryFactory->get('brightcove_video')
      ->condition('api_client', $entity->id())
      ->execute();
    foreach ($videos as $video) {
      $this->videoLocalDeleteQueue->createItem($video);
    }

    // Collect all players belonging for the api client.
    $players = $this->queryFactory->get('brightcove_player')
      ->condition('api_client', $entity->id())
      ->execute();
    foreach ($players as $player) {
      $this->playerDeleteQueue->createItem(['player_entity_id' => $player]);
    }

    // Collect all custom fields belonging for the api client.
    $custom_fields = $this->queryFactory->get('brightcove_custom_field')
      ->condition('api_client', $entity->id())
      ->execute();
    foreach ($custom_fields as $custom_field) {
      $this->customFieldDeleteQueue->createItem($custom_field);
    }

    // First delete the default subscription from Brightcove if it's active.
    $default_subscription = BrightcoveSubscription::loadDefault($entity);
    if (!empty($default_subscription)) {
      if ($default_subscription->isActive()) {
        $default_subscription->delete();
      }
      else {
        $default_subscription->delete(TRUE);
      }
    }

    // Then collect all available subscriptions belonging to the given API
    // client, and put them into the delete queue.
    $brightcove_subscriptions = BrightcoveSubscription::loadMultipleByApiClient($entity);
    foreach ($brightcove_subscriptions as $brightcove_subscription) {
      $this->subscriptionDeleteQueue->createItem([
        'subscription_id' => $brightcove_subscription->getBcSid(),
        'local_only' => TRUE,
      ]);
    }

    // Initialize batch.
    batch_set([
      'operations' => [
        [
          [BrightcoveUtil::class, 'runQueue'], ['brightcove_playlist_local_delete_queue_worker'],
        ],
        [
          [BrightcoveUtil::class, 'runQueue'], ['brightcove_video_local_delete_queue_worker'],
        ],
        [
          [BrightcoveUtil::class, 'runQueue'], ['brightcove_player_delete_queue_worker'],
        ],
        [
          [BrightcoveUtil::class, 'runQueue'], ['brightcove_custom_field_delete_queue_worker'],
        ],
        [
          [BrightcoveUtil::class, 'runQueue'], ['brightcove_text_track_delete_queue_worker'],
        ],
        [
          [BrightcoveUtil::class, 'runQueue'], ['brightcove_subscription_delete_queue_worker'],
        ],
      ],
    ]);

    // Delete api client.
    $entity->delete();
    drupal_set_message($this->t('Entity @type: deleted @label.', [
      '@type' => $this->entity->bundle(),
      '@label' => $this->entity->label(),
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
