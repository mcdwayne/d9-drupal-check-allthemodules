<?php

namespace Drupal\link_header_pager\Plugin\views\pager;

interface LinkHeaderPagerInterface {

  /**
   * Get the header value.
   *
   * @return string
   *   Header value.
   */
  public function getHeader();

}
