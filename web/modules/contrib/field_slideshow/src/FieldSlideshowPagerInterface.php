<?php

namespace Drupal\field_slideshow;

/**
 * Interface for field_slideshow_pager plugins.
 */
interface FieldSlideshowPagerInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

}
