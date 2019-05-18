<?php

namespace Drupal\dynamictagclouds\Plugin\TagCloud;

use Drupal\dynamictagclouds\Plugin\TagCloudBase;


/**
 * Default tag cloud style.
 *
 * @TagCloud(
 *  id = "default_tag_cloud",
 *  label = @Translation("Default"),
 *  libraries = {
 *   "dynamictagclouds/default_tag_cloud"
 *  },
 *  template = {
 *    "type" = "module",
 *    "name" = "dynamictagclouds",
 *    "directory" = "templates",
 *    "file" = "default-tag-clouds"
 *  }
 * )
 */
class DefaultTagCloud extends TagCloudBase {

  /**
   * {@inheritdoc}
   */
  public function build($tags) {
    $build = parent::build($tags);

    return $build;
  }

}
