<?php

namespace Drupal\panels_extended\BlockConfig;

/**
 * Interface for adding info about a block to the admin panel content form.
 */
interface AdminInfoInterface {

  /**
   * Supplies primary information about the block.
   *
   * This information is added to the panel content form.
   *
   * Example usage: "Selected tags: news, weather".
   *
   * @return string|null
   *   The summary of the configured setting(s).
   *   Return NULL to avoid a summary being added.
   */
  public function getAdminPrimaryInfo();

  /**
   * Gets secondary (less important) information about the block.
   *
   * This information is added to the panel content form.
   *
   * This information is (by default) added in a 'details' block which can hold
   * a summary of the data and extended information.
   * The extended information is visible when the details isn't collapsed.
   *
   * Example usage: ["#items: 5", "Number of items per page: 5"].
   *
   * @return array|null
   *   2 values: the short version and the longer version.
   *   Return NULL to add nothing.
   */
  public function getAdminSecondaryInfo();

}
