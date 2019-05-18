<?php

namespace Drupal\qwantsearch\Service;

/**
 * Class QwantSearchDisplayInterface.
 */
interface QwantSearchDisplayInterface {

  /**
   * Prepares a renderable array of results.
   *
   * @param array $results
   *   Qwant search results.
   *
   * @return array
   *   Renderable array.
   */
  public function prepareRenderableResults(array $results);

  /**
   * Generates a thumbnail for search result using module imagecache_external.
   *
   * @param array $medias
   *   Medias returned in Qwant json response.
   *
   * @return array
   *   Renderable array for picture using configured image style.
   */
  public function generateResultImage(array $medias);

}
