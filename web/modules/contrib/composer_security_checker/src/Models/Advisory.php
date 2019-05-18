<?php

namespace Drupal\composer_security_checker\Models;

/**
 * Class Update.
 *
 * @package Drupal\composer_security_checker\Models
 */
class Advisory {

  protected $libraryName;

  protected $libraryVersion;

  protected $advisoryIdentifier;

  protected $advisoryTitle;

  protected $advisoryLink;

  protected $cve;

  /**
   * Advisory constructor.
   *
   * @param string $library_name
   *   The library_name as provided by the security checker service.
   * @param string $library_version
   *   The library_version as provided by the security checker service.
   * @param string $advisory_identifier
   *   The security advisory date or CVE identifier as provided by the security
   *   checker service.
   * @param string $advisory_title
   *   The security advisory title as provided by the security checker service.
   * @param string $advisory_link
   *   The security advisory link as provided by the security checker service.
   */
  public function __construct($library_name, $library_version, $advisory_identifier, $advisory_title, $advisory_link) {
    $this->libraryName = $library_name;
    $this->libraryVersion = $library_version;
    $this->advisoryIdentifier = $advisory_identifier;
    $this->advisoryTitle = $advisory_title;
    $this->advisoryLink = $advisory_link;
  }

  /**
   * Get the libraryName property of this security advisory instance.
   *
   * @return string
   *   The libraryName property of this security advisory instance.
   */
  public function getLibraryName() {
    return $this->libraryName;
  }

  /**
   * Get the libraryVersion property of this security advisory instance.
   *
   * @return string
   *   The libraryVersion property of this security advisory instance.
   */
  public function getLibraryVersion() {
    return $this->libraryVersion;
  }

  /**
   * Get the advisoryIdentifier property of this security advisory instance.
   *
   * @return string
   *   The advisoryIdentifier property of this security advisory instance.
   */
  public function getAdvisoryIdentifier() {
    return $this->advisoryIdentifier;
  }

  /**
   * Get the advisoryTitle property of this security advisory instance.
   *
   * @return string
   *   The advisoryTitle property of this security advisory instance.
   */
  public function getAdvisoryTitle() {
    return $this->advisoryTitle;
  }

  /**
   * Get the advisoryLink property of this security advisory instance.
   *
   * @return string
   *   The advisoryLink property of this security advisory instance.
   */
  public function getAdvisoryLink() {
    return $this->advisoryLink;
  }

}
