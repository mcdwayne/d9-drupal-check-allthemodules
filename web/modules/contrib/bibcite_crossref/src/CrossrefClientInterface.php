<?php

namespace Drupal\bibcite_crossref;

/**
 * Define an interface for CrossrefClient service.
 */
interface CrossrefClientInterface {

  /**
   * Retrieve work metadata by DOI.
   *
   * @param string $doi
   *   Digital Object Identifiers of a work to lookup. Ex. "10.1037/0003-066X.59.1.29".
   *
   * @return array
   *   Response JSON decoded as array.
   */
  public function lookupDoi($doi);

  /**
   * Retrieve work metadata by DOI as raw JSON string.
   *
   * @param string $doi
   *   Digital Object Identifiers of a work to lookup. Ex. "10.1037/0003-066X.59.1.29".
   *
   * @return string
   *   Response JSON.
   */
  public function lookupDoiRaw($doi);

}
