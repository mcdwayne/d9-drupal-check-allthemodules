<?php

namespace Drupal\uc_cart_links;

/**
 * Utility functions for dealing with Cart Links.
 */
interface CartLinksValidatorInterface {

  /**
   * Tests a string containing a Cart Link to see if it is syntactically valid.
   *
   * @param string $link
   *   String containing a Cart Link.
   * @param bool $debug
   *   Boolean flag to enable/disable debug output. Defaults to FALSE.
   *
   * @return bool
   *   TRUE if valid, FALSE if not.
   */
  public function isValidSyntax($link, $debug = FALSE);

}
