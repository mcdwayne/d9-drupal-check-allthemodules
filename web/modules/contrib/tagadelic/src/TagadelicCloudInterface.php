<?php

namespace Drupal\tagadelic;

use Drupal\tagadelic\TagadelicTag;

/**
 * Provides an interface for returning tags for a tag cloud.
 */
interface TagadelicCloudInterface {

  /**
   * Return an array of Tagadelic Tags.
   *
   * @return $this-tags.
   */
  public function getTags();

  /**
   * Add a TagadelicTag object to the tags array.
   *
   * @param $tag. The tag being added.
   */
  public function addTag(TagadelicTag $tag);
}
