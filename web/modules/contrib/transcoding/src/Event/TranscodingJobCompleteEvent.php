<?php

namespace Drupal\transcoding\Event;

use Drupal\media\MediaInterface;
use Drupal\transcoding\TranscodingJobInterface;

class TranscodingJobCompleteEvent extends TranscodingJobEvent {

  /**
   * The media slated for creation.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $media;

  /**
   * @inheritDoc
   */
  public function __construct(TranscodingJobInterface $job, MediaInterface $media) {
    parent::__construct($job);
    $this->media = $media;
  }

  /**
   * Media getter.
   * @return \Drupal\media\MediaInterface
   */
  public function getMedia() {
    return $this->media;
  }

}
