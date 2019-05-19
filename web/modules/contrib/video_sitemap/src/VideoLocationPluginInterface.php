<?php

namespace Drupal\video_sitemap;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\media\MediaInterface;

/**
 * Providers an interface for embed providers.
 */
interface VideoLocationPluginInterface extends PluginInspectionInterface {

  /**
   * Get thumbnail src.
   *
   * This is used for <video:thumbnail_loc> sitemap tag.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A media item..
   *
   * @return string
   *   The URL to thumbnail file.
   */
  public function getThumbnailLoc(MediaInterface $media);

  /**
   * Get URL pointing to a player for the video.
   *
   * This is used for <video:player_loc> sitemap tag.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A media item.
   *
   * @return string
   *   The URL to the video player.
   */
  public function getPlayerLoc(MediaInterface $media);

  /**
   * Get URL pointing to the actual video media file.
   *
   * This is used for <video:content_loc> sitemap tag.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A media item.
   *
   * @return string
   *   The URL to the video media file.
   */
  public function getContentLoc(MediaInterface $media);

}
