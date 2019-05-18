<?php

/**
 * @file
 */

namespace Drupal\media_entity_vimeo;

/**
 * Defines a wrapper around the Vimeo oEmbed call.
 */
interface VimeoEmbedFetcherInterface {

  /**
   * Retrieves a vimeo post by its video url.
   *
   * @param string $video_url
   *   The url of vimeo video.
   *
   * @return array
   *   The vimeo video information.
   */
  public function fetchVimeoEmbed($video_url);

}
