<?php

namespace Drupal\dynamictagclouds\Plugin\TagCloud;

use Drupal\dynamictagclouds\Plugin\TagCloudBase;


/**
 * @TagCloud(
 *  id = "index_tag_cloud",
 *  label = @Translation("Index"),
 *  libraries = {
 *   "dynamictagclouds/index_tag_cloud"
 *  },
 *  template = {
 *    "type" = "module",
 *    "name" = "dynamictagclouds",
 *    "directory" = "templates",
 *    "file" = "index-tag-clouds"
 *  }
 * )
 */
class IndexTagCloud extends TagCloudBase {

  /**
   * {@inheritdoc}
   */
  public function build($tags) {
    $tags_index = [];
    // Create first char index of tags.
    foreach ($tags as $tag) {
      $tags_index[strtoupper($tag['name'][0])][] = $tag;
    }
    // Sort tag index naturally, ie; numbers first and then alphabets.
    ksort($tags_index, SORT_NATURAL);
    $build = parent::build($tags_index);

    return $build;
  }

}
