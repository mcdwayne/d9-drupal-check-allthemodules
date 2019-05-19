<?php

namespace Drupal\text_or_link;

use Drupal\link\LinkItemInterface;

/**
 * Defines an interface for the text_or_link field item.
 */
interface TextOrLinkItemInterface extends LinkItemInterface {

  /**
   * Determines whether the uri data is empty.
   *
   * @return bool
   *   TRUE if the uri data is empty, FALSE otherwise.
   */
  public function isUriEmpty();

}
