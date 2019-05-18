<?php

namespace Drupal\client_hints\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\image\Plugin\ImageEffect\ResizeImageEffect;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Client hints service.
 */
class ClientHints {

  const ROUTE = 'image';

  /**
   * The default cache.
   *
   * @var CacheBackendInterface
   */
  protected $defaultCache;

  /**
   * The entity type manager.
   *
   * @var EntityTypeManager
   */
  protected $entityManager;

  /**
   * ClientHints constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $default_cache
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   */
  public function __construct(CacheBackendInterface $default_cache, EntityTypeManager $entity_type_manager) {
    $this->defaultCache = $default_cache;
    $this->entityManager = $entity_type_manager;
  }

  /**
   * @param $uri
   * @param int $dpr
   * @param $width
   *
   * @return \Drupal\Core\GeneratedUrl|string
   */
  public function getImageRedirectUrl($url, $dpr = 1, $width) {

    $image_style_id = $this->getAppropriateImageStyleId($width * $dpr);

    $image_style = $this->entityManager->getStorage('image_style')->load($image_style_id);

    $uri = $this->getImageUri($url);

    /** @var $image_style \Drupal\image\Entity\ImageStyle */
    return $image_style->buildUrl($uri);

  }

  /**
   * @return mixed
   */
  protected function getImageStylesByWidth() {

    if ($cached = $this->defaultCache->get('client_hints.styles')) {

      // Return the cached options.
      return $cached->data;

    } else {

      // Get image styles.
      $image_styles = $this->entityManager->getStorage('image_style')->loadMultiple();

      foreach ($image_styles as $style) {

        // Get image style's output width...
        $width = $this->getImageStyleWidth($style);

        // ...and use that to key the style id in a list of image styles.
        $image_styles_by_width[$width] = $style->id();

      }

      // Sort the image style list.
      ksort($image_styles_by_width);

      $now = new \DateTime();
      $this->defaultCache->set('client_hints.styles', $image_styles_by_width, $now->modify('+5 minute')->getTimestamp());

      return $image_styles_by_width;

    }

  }

  /**
   * @param $width
   *
   * @return mixed
   */
  protected function getAppropriateImageStyleId($width) {

    $image_styles = $this->getImageStylesByWidth();

    $bigger_image_styles = array_filter($image_styles, function ($x) use ($width) {
      return $x > $width;
    }, ARRAY_FILTER_USE_KEY);

    if (!empty($bigger_image_styles)) {

      // Return smallest appropriate image style's id.
      return array_shift($bigger_image_styles);

    } else {

      // Otherwise, return biggest image style's id.
      return array_pop($image_styles);

    }

  }

  /**
   * @param $style
   *
   * @return int
   */
  protected function getImageStyleWidth($style) {

    /** @var \Drupal\image\Entity\ImageStyle $style */
    $effects = $style->getEffects();

    $width = 0;

    foreach ($effects as $effect) {

      if ($effect instanceof ResizeImageEffect) {
        $width = $effect->getConfiguration()['data']['width'];
      }

    }

    return $width;

  }

  /**
   * @param $image_url
   *
   * @return string
   */
  public function getImageUri($image_url): string {

    $default_scheme = file_default_scheme();
    $default_stream_wrapper = \Drupal::service('stream_wrapper_manager')
      ->getViaScheme($default_scheme);

    if ($default_stream_wrapper instanceof LocalStream) {
      $streamwrapper = $default_stream_wrapper->getUri();
    } else {
      $streamwrapper = $default_stream_wrapper->dirname();
    }

    $uri = str_replace($default_stream_wrapper->getDirectoryPath(), '', $streamwrapper . $image_url);
    return $uri;

  }

}
