<?php

namespace Drupal\brightcove;

use Brightcove\API\CMS;
use Brightcove\API\DI;
use Brightcove\API\Exception\APIException;
use Brightcove\API\PM;
use Drupal\brightcove\Entity\BrightcoveAPIClient;
use Drupal\brightcove\Entity\BrightcovePlayer;
use Drupal\brightcove\Entity\BrightcovePlaylist;
use Drupal\brightcove\Entity\BrightcoveVideo;
use Drupal\brightcove\Exception\BrightcoveUtilException;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueGarbageCollectionInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;

/**
 * Utility class for Brightcove.
 */
class BrightcoveUtil {
  /**
   * Array of BrightcoveAPIClient objects.
   *
   * @var \Drupal\brightcove\Entity\BrightcoveAPIClient[]
   */
  protected static $apiClients = [];

  /**
   * Array of CMS objects.
   *
   * @var \Brightcove\API\CMS[]
   */
  protected static $cmsApis = [];

  /**
   * Array of DI objects.
   *
   * @var \Brightcove\API\DI[]
   */
  protected static $diApis = [];

  /**
   * Array of PM objects.
   *
   * @var \Brightcove\API\PM[]
   */
  protected static $pmApis = [];

  /**
   * Convert Brightcove date make it digestible by Drupal.
   *
   * @param string $brightcove_date
   *   Brightcove date format.
   *
   * @return string|null
   *   Drupal date format.
   */
  public static function convertDate($brightcove_date) {
    if (empty($brightcove_date)) {
      return NULL;
    }

    return preg_replace('/\.\d{3}Z$/i', '', $brightcove_date);
  }

  /**
   * Gets BrightcoveAPIClient entity.
   *
   * @param string $entity_id
   *   The entity ID of the BrightcoveAPIClient.
   *
   * @return \Drupal\brightcove\Entity\BrightcoveAPIClient
   *   Loaded BrightcoveAPIClient object.
   */
  public static function getApiClient($entity_id) {
    // Load BrightcoveAPIClient if it wasn't already.
    if (!isset(self::$apiClients[$entity_id])) {
      self::$apiClients[$entity_id] = BrightcoveAPIClient::load($entity_id);
    }

    return self::$apiClients[$entity_id];
  }

  /**
   * Gets Brightcove client.
   *
   * @param string $entity_id
   *   BrightcoveAPIClient entity ID.
   *
   * @return \Brightcove\API\Client
   *   Loaded Brightcove client.
   */
  public static function getClient($entity_id) {
    $api_client = self::getApiClient($entity_id);
    return $api_client->getClient();
  }

  /**
   * Gets Brightcove CMS API.
   *
   * @param string $entity_id
   *   BrightcoveAPIClient entity ID.
   *
   * @return \Brightcove\API\CMS
   *   Initialized Brightcove CMS API.
   */
  public static function getCmsApi($entity_id) {
    // Create new \Brightcove\API\CMS object if it is not exists yet.
    if (!isset(self::$cmsApis[$entity_id])) {
      $client = self::getClient($entity_id);
      self::$cmsApis[$entity_id] = new CMS($client, self::$apiClients[$entity_id]->getAccountId());
    }

    return self::$cmsApis[$entity_id];
  }

  /**
   * Gets Brightcove DI API.
   *
   * @param string $entity_id
   *   BrightcoveAPIClient entity ID.
   *
   * @return \Brightcove\API\DI
   *   Initialized Brightcove CMS API.
   */
  public static function getDiApi($entity_id) {
    // Create new \Brightcove\API\DI object if it is not exists yet.
    if (!isset(self::$diApis[$entity_id])) {
      $client = self::getClient($entity_id);
      self::$diApis[$entity_id] = new DI($client, self::$apiClients[$entity_id]->getAccountId());
    }

    return self::$diApis[$entity_id];
  }

  /**
   * Gets Brightcove PM API.
   *
   * @param string $entity_id
   *   BrightcoveAPIClient entity ID.
   *
   * @return \Brightcove\API\PM
   *   Initialized Brightcove PM API.
   */
  public static function getPmApi($entity_id) {
    // Create new \Brightcove\API\PM object if it is not exists yet.
    if (!isset(self::$pmApis[$entity_id])) {
      $client = self::getClient($entity_id);
      self::$pmApis[$entity_id] = new PM($client, self::$apiClients[$entity_id]->getAccountId());
    }

    return self::$pmApis[$entity_id];
  }

  /**
   * Check updated version of the CMS entity.
   *
   * If the checked CMS entity has a newer version of it on Brightcove then
   * show a message about it with a link to be able to update the local
   * version.
   *
   * @param \Drupal\brightcove\BrightcoveCMSEntityInterface $entity
   *   Brightcove CMS Entity, can be BrightcoveVideo or BrightcovePlaylist.
   *   Player is currently not supported.
   *
   * @throws \Exception
   *   If the version for the given entity is cannot be checked.
   */
  public static function checkUpdatedVersion(BrightcoveCMSEntityInterface $entity) {
    $client = self::getClient($entity->getApiClient());

    if (!is_null($client)) {
      $cms = self::getCmsApi($entity->getApiClient());

      $entity_type = '';
      try {
        if ($entity instanceof BrightcoveVideo) {
          $entity_type = 'video';
          $cms_entity = $cms->getVideo($entity->getVideoId());
        }
        elseif ($entity instanceof BrightcovePlaylist) {
          $entity_type = 'playlist';
          $cms_entity = $cms->getPlaylist($entity->getPlaylistId());
        }
        else {
          throw new \Exception(t("Can't check version for :entity_type entity.", [
            ':entity_type' => get_class($entity),
          ]));
        }

        if (isset($cms_entity)) {
          if ($entity->getChangedTime() < strtotime($cms_entity->getUpdatedAt())) {
            $url = Url::fromRoute("brightcove_manual_update_{$entity_type}", ['entity_id' => $entity->id()], ['query' => ['token' => \Drupal::getContainer()->get('csrf_token')->get("brightcove_{$entity_type}/{$entity->id()}/update")]]);

            drupal_set_message(t("There is a newer version of this :type on Brightcove, you may want to <a href=':url'>update the local version</a> before editing it.", [
              ':type' => $entity_type,
              ':url' => $url->toString(),
            ]), 'warning');
          }
        }
      }
      catch (APIException $e) {
        if (!empty($entity_type)) {
          $url = Url::fromRoute("entity.brightcove_{$entity_type}.delete_form", ["brightcove_{$entity_type}" => $entity->id()]);
          drupal_set_message(t("This :type no longer exists on Brightcove. You may want to <a href=':url'>delete the local version</a> too.", [
            ':type' => $entity_type,
            ':url' => $url->toString(),
          ]), 'error');
        }
        else {
          drupal_set_message($e->getMessage(), 'error');
        }
      }
    }
    else {
      drupal_set_message(t('Brightcove API connection error: :error', [
        ':error' => self::getApiClient($entity->getApiClient())->getClientStatusMessage(),
      ]), 'error');
    }
  }

  /**
   * Gets Brightcove status queues.
   *
   * @return array
   *   A list of status queues.
   */
  public static function getStatusQueues() {
    // These queues are responsible for synchronizing from Brightcove to
    // Drupal (IOW pulling). The order is important.
    // - The client queue must be run first, that's out of question: this
    //   worker populates most of the other queues.
    // - Players should be pulled before videos and playlists.
    // - Custom fields (which means custom field definitions, not values)
    //   should be pulled before videos.
    // - Text tracks can only be pulled after videos.
    // - Playlists can only be pulled after videos.
    // - Custom fields (again: their definitions) have to be deleted
    //   before pulling videos.
    // - Text tracks have to be deleted before videos are pulled or
    //   deleted.
    return [
      'brightcove_client_queue_worker',
      'brightcove_player_queue_worker',
      'brightcove_player_delete_queue_worker',
      'brightcove_custom_field_queue_worker',
      'brightcove_custom_field_delete_queue_worker',
      'brightcove_video_page_queue_worker',
      'brightcove_video_queue_worker',
      'brightcove_text_track_queue_worker',
      'brightcove_text_track_delete_queue_worker',
      'brightcove_playlist_page_queue_worker',
      'brightcove_playlist_queue_worker',
      'brightcove_video_delete_queue_worker',
      'brightcove_playlist_delete_queue_worker',
      'brightcove_subscriptions_queue_worker',
      'brightcove_subscription_queue_worker',
      'brightcove_subscription_delete_queue_worker',
    ];
  }

  /**
   * Runs specific status queues based on the given $type.
   *
   * @param string $type
   *   The queue's type to run, it can be either sync, run or clear.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   Queue factory.
   *
   * @throws \Drupal\brightcove\Exception\BrightcoveUtilException
   *   Throws an exception if the queue type is invalid.
   */
  public static function runStatusQueues($type, QueueFactory $queue_factory) {
    $queues = self::getStatusQueues();

    $batch_operations = [];

    switch ($type) {
      case 'sync':
        $batch_operations[] = ['_brightcove_initiate_sync', []];
        // There is intentionally no break here.
      case 'run':
        $queue_type = 'runQueue';
        break;

      case 'clear':
        $queue_type = 'clearQueue';
        break;

      default:
        throw new BrightcoveUtilException('Invalid queue type.');
    }

    // Build queue operations array.
    foreach ($queues as $queue) {
      $batch_operations[] = [[static::class, $queue_type], [$queue]];
    }

    if ($batch_operations) {
      // Clean-up expired items in the default queue implementation table. If
      // that's not used, this will simply be a no-op.
      // @see system_cron()
      foreach ($queues as $queue) {
        $queue = $queue_factory->get($queue);
        if ($queue instanceof QueueGarbageCollectionInterface) {
          $queue->garbageCollection();
        }
      }

      batch_set([
        'operations' => $batch_operations,
      ]);
    }
  }

  /**
   * Runs a queue.
   *
   * @param string $queue
   *   The queue name to clear.
   * @param mixed &$context
   *   The Batch API context.
   */
  public static function runQueue($queue, &$context) {
    // This is a static function called by Batch API, so it's not possible to
    // use dependency injection here.
    /* @var \Drupal\Core\Queue\QueueWorkerInterface $queue_worker */
    $queue_worker = \Drupal::getContainer()->get('plugin.manager.queue_worker')->createInstance($queue);
    $queue = \Drupal::queue($queue);

    // Let's process ALL the items in the queue, 5 by 5, to avoid PHP timeouts.
    // If there's any problem with processing any of those 5 items, stop sooner.
    $limit = 5;
    $handled_all = TRUE;
    while (($limit > 0) && ($item = $queue->claimItem(5))) {
      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
      }
      catch (SuspendQueueException $e) {
        $queue->releaseItem($item);
        $handled_all = FALSE;
        break;
      }
      catch (APIException $e) {
        if ($e->getCode() == 401) {
          $queue->deleteItem($item);
          $handled_all = TRUE;
        }
        else {
          watchdog_exception('brightcove', $e);
          \Drupal::logger('brightcove')->error($e->getMessage());
          $handled_all = FALSE;
        }
      }
      catch (\Exception $e) {
        watchdog_exception('brightcove', $e);
        \Drupal::logger('brightcove')->error($e->getMessage());
        $handled_all = FALSE;
      }
      $limit--;
    }

    // As this batch may be run synchronously with the queue's cron processor,
    // we can't be sure about the number of items left for the batch as long as
    // there is any. So let's just inform the user about the number of remaining
    // items, as we don't really care if they are processed by this batch
    // processor or the cron one.
    $remaining = $queue->numberOfItems();
    $context['message'] = t('@count item(s) left in current queue', ['@count' => $remaining]);
    $context['finished'] = $handled_all && ($remaining == 0);
  }

  /**
   * Clears a queue.
   *
   * @param string $queue
   *   The queue name to clear.
   */
  public static function clearQueue($queue) {
    // This is a static function called by Batch API, so it's not possible to
    // use dependency injection here.
    \Drupal::queue($queue)->deleteQueue();
  }

  /**
   * Helper function to get default player for the given entity.
   *
   * @param \Drupal\brightcove\BrightcoveVideoPlaylistCMSEntityInterface $entity
   *   Video or playlist entity.
   *
   * @return string
   *   The ID of the player.
   */
  public static function getDefaultPlayer(BrightcoveVideoPlaylistCMSEntityInterface $entity) {
    if ($player = $entity->getPlayer()) {
      return BrightcovePlayer::load($player)->getPlayerId();
    }

    $api_client = self::getApiClient($entity->getApiClient());
    return $api_client->getDefaultPlayer();
  }

  /**
   * Helper function to save or update tags.
   *
   * @param \Drupal\brightcove\BrightcoveVideoPlaylistCMSEntityInterface $entity
   *   Video or playlist entity.
   * @param string $api_client_id
   *   API Client ID.
   * @param array $tags
   *   The list of tags from brightcove.
   */
  public static function saveOrUpdateTags(BrightcoveVideoPlaylistCMSEntityInterface $entity, $api_client_id, array $tags = []) {
    $entity_tags = [];
    $video_entity_tags = $entity->getTags();
    foreach ($video_entity_tags as $index => $tag) {
      /** @var \Drupal\taxonomy\Entity\Term $term */
      $term = Term::load($tag['target_id']);
      if (!is_null($term)) {
        $entity_tags[$term->id()] = $term->getName();
      }
      // Remove non-existing tag references from the video, if there would
      // be any.
      else {
        unset($video_entity_tags[$index]);
        $entity->setTags($video_entity_tags);
      }
    }
    if (array_values($entity_tags) != $tags) {
      // Remove deleted tags from the video.
      if (!empty($entity->id())) {
        $tags_to_remove = array_diff($entity_tags, $tags);
        foreach (array_keys($tags_to_remove) as $entity_id) {
          unset($entity_tags[$entity_id]);
        }
      }

      // Add new tags.
      $new_tags = array_diff($tags, $entity_tags);
      $entity_tags = array_keys($entity_tags);
      foreach ($new_tags as $tag) {
        $taxonomy_term = NULL;
        $existing_tags = \Drupal::entityQuery('taxonomy_term')
          ->condition('vid', BrightcoveVideo::TAGS_VID)
          ->condition('name', $tag)
          ->execute();

        // Create new Taxonomy term item.
        if (empty($existing_tags)) {
          $values = [
            'name' => $tag,
            'vid' => BrightcoveVideo::TAGS_VID,
            'brightcove_api_client' => [
              'target_id' => $api_client_id,
            ],
          ];
          $taxonomy_term = Term::create($values);
          $taxonomy_term->save();
        }
        $entity_tags[] = isset($taxonomy_term) ? $taxonomy_term->id() : reset($existing_tags);
      }
      $entity->setTags($entity_tags);
    }
  }

  /**
   * Returns the absolute URL path for the notification callback.
   *
   * @return string
   *   The absolute URL path for the notification callback.
   */
  public static function getDefaultSubscriptionUrl() {
    return Url::fromRoute('brightcove_notification_callback', [], ['absolute' => TRUE])->toString();
  }

  /**
   * Run a piece of code with semaphore check.
   *
   * @param callable $function
   *   Function that needs to be run in sync.
   *
   * @return bool|mixed
   *   FALSE if the execution was failed, otherwise it will return what the
   *   callable function returned.
   */
  public static function runWithSemaphore(callable $function) {
    /* @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface $state */
    $state = \Drupal::getContainer()->get('state');

    try {
      // Basic semaphore to prevent race conditions, this is needed
      // because Brightcove may call callbacks again before the previous one
      // would finish.
      //
      // To make sure that the waiting doesn't run indefinitely limit the
      // maximum iterations to 600 cycles, which in worst case scenario would
      // mean 5 minutes maximum wait time.
      $limit = 600;
      for ($i = 0; $i < $limit; $i++) {
        // Try to acquire semaphore.
        for (; $i < $limit && $state->get('brightcove_semaphore', FALSE) == TRUE; $i++) {
          // Wait random time between 100 and 500 milliseconds on each
          // try.
          usleep(mt_rand(100000, 500000));
        }

        // Make sure that other processes have not acquired the semaphore
        // while we waited.
        if ($state->get('brightcove_semaphore', FALSE) == FALSE) {
          // Acquire semaphore as soon as we can.
          $state->set('brightcove_semaphore', TRUE);
          break;
        }
      }

      // If we couldn't acquire the semaphore in the given time, release the
      // semaphore (finally block will do this), and return with FALSE.
      if (600 <= $i) {
        return FALSE;
      }

      // Run function.
      return $function();
    }
    catch (\Exception $e) {
      // Log error, and return with FALSE.
      watchdog_exception('brightcove', $e, $e->getMessage());
      return FALSE;
    }
    finally {
      // Release semaphore.
      // This will always run regardless what happened.
      $state->set('brightcove_semaphore', FALSE);
    }
  }

}
