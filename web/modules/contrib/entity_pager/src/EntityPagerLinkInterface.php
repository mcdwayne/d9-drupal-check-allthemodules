<?php

namespace Drupal\entity_pager;

/**
 * An interface for a single Entity Pager Link.
 */
interface EntityPagerLinkInterface {
  /**
   * Returns a render array for the link.
   *
   * @return array
   *   A render array for the link
   */
  public function getLink();
}
