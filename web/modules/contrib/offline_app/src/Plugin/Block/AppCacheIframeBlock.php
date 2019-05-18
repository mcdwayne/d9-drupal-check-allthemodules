<?php

/**
 * @file
 * Contains \Drupal\offline_app\Plugin\Block\AppCacheIframeBlock.
 */

namespace Drupal\offline_app\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides the 'AppCacheIframeBlock' block.
 *
 * @Block(
 *  id = "offline_app_app_cache_iframe_block",
 *  admin_label = @Translation("AppCache iframe"),
 * )
 */
class AppCacheIframeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#markup'] = '<iframe src="' . Url::fromRoute('offline_app.appcache.iframe')->toString() . '" frameborder="0" scrolling="no" width="0" height="0"></iframe>';
    $build['#allowed_tags'] = ['iframe'];
    return $build;
  }

}
