<?php

namespace Drupal\bridtv\Field;

/**
 * Interface for Brid.TV video information by a field item.
 */
interface BridtvVideoItemInterface {

  /**
   * Get the video instance to embed, provided by the item.
   *
   * @return \Drupal\bridtv\BridEmbeddingInstance|null
   *   The video instance, or NULL if not given.
   */
  public function getBridEmbeddingInstance();

}
