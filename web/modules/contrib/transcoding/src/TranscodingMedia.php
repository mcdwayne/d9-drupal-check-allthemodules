<?php

namespace Drupal\transcoding;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\transcoding\Event\TranscodingJobCompleteEvent;

/**
 * Class TranscodingMedia.
 *
 * @package Drupal\transcoding
 */
class TranscodingMedia {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructor.
   */
  public function __construct(EventDispatcherInterface $eventDispatcher) {
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * Scaffold a media entity.
   *
   * @param \Drupal\transcoding\TranscodingJobInterface $job
   * @return \Drupal\media\MediaInterface
   */
  protected function buildMedia(TranscodingJobInterface $job) {
    $media = Media::create([
      'bundle' => $job->get('media_bundle')->entity->id(),
      'uid' => $job->getOwnerId(),
    ]);
    return $media;
  }

  /**
   * Process a complete job, creating a resulting Media entity.
   *
   * @param \Drupal\transcoding\TranscodingJobInterface $job
   * @param string $uri URI, if it's known at this stage.
   */
  public function complete(TranscodingJobInterface $job, $uri = '') {
    $media = $this->buildMedia($job);
    $event = new TranscodingJobCompleteEvent($job, $media);
    $this->eventDispatcher->dispatch(TranscodingJobEvents::COMPLETE, $event);
    // If none of the processing has yet set a file (e.g., after moving), set now.
    if ($uri && $media->get($job->getMediaTargetFieldName())->isEmpty()) {
      $file = File::create([
        'uri' => $uri,
      ]);
      $file->save();
      $media->set($job->getMediaTargetFieldName(), [
        'target_id' => $file->id(),
      ]);
    }
    $media->save();
    $job->set('status', TranscodingStatus::COMPLETE)
      ->set('media', ['target_id' => $media->id()])
      ->save();
  }

}
