<?php

namespace Drupal\composer_security_checker\Parsers;

use Drupal\composer_security_checker\Collections\AdvisoryCollection;
use Drupal\composer_security_checker\Models\Advisory;

/**
 * Class SensioLabsSecurityCheckerParser.
 *
 * @package Drupal\composer_security_checker\Parsers
 */
class SensioLabsSecurityCheckerParser {

  /**
   * A single response item from a SensioLabs Security Checker response.
   *
   * @var array
   */
  private $securityAdvisories;

  /**
   * A collection item to have parsed advisories parsed into.
   *
   * @var \Drupal\composer_security_checker\Collections\AdvisoryCollection
   */
  private $collection;

  /**
   * The title of the library that is being parsed.
   *
   * @var string
   */
  private $libraryTitle;

  /**
   * SensioLabsSecurityCheckerParser constructor.
   *
   * @param string $library_title
   *   The title of the library that is being parsed.
   * @param array $security_advisories
   *   A single response item from a SensioLabs Security Checker response.
   */
  public function __construct($library_title, array $security_advisories) {
    $this->securityAdvisories = $security_advisories;
    $this->collection = new AdvisoryCollection();
    $this->libraryTitle = $library_title;
  }

  /**
   * Parse the results of the security check.
   *
   * @return AdvisoryCollection
   *   A collection object containing any potential security vulnerabilities.
   */
  public function parse() {

    foreach ($this->securityAdvisories['advisories'] as $advisory_item_key => $advisory_item) {

      $advisory = new Advisory(
        $this->libraryTitle,
        $this->parseVersion(),
        $this->parseIdentifier($advisory_item_key, $advisory_item),
        $advisory_item['title'],
        $advisory_item['link']
      );

      $this->collection->add($advisory);

    }

    return $this->collection;

  }

  /**
   * Get the version of a library.
   *
   * @return string
   *   The version number of a security advisory.
   */
  private function parseVersion() {
    return $this->securityAdvisories['version'];
  }

  /**
   * Get the identifier for a security vulnerability.
   *
   * This will generally either be a date or a CVE identifier.
   *
   * @param string $advisory_item_key
   *   The default key (i.e. date) for a security advisory.
   * @param array $advisory_item
   *   A full security item containing any possible identifier keys.
   *
   * @return string
   *   The identifier for a security vulnerability.
   */
  private function parseIdentifier($advisory_item_key, array $advisory_item) {
    if (!empty($advisory_item['cve'])) {
      return $advisory_item['cve'];
    }

    return $advisory_item_key;
  }

}
