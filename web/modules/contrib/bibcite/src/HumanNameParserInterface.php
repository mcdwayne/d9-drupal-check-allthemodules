<?php

namespace Drupal\bibcite;

/**
 * Define an interface for HumanNameParser service.
 */
interface HumanNameParserInterface {

  /**
   * Parse the name into its constituent parts.
   *
   * @param string $name
   *   Human name string.
   *
   * @return array
   *   Parsed name parts.
   */
  public function parse($name);

}
