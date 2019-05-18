<?php

namespace Drupal\comscore_direct;

use Drupal\Core\Config\Config;

class ComscoreInformation {

  protected $siteId;

  protected $genre;

  protected $package;

  protected $segment;

  protected $currentUrl;

  /**
   * Creates a new ComscoreInformation instance.
   *
   * @param $siteId
   * @param $genre
   * @param $package
   * @param $segment
   * @param $currentUrl
   */

  public function __construct($siteId, $genre, $package, $segment, $currentUrl) {
    $this->siteId = $siteId;
    $this->genre = $genre;
    $this->package = $package;
    $this->segment = $segment;
    $this->currentUrl = $currentUrl;
  }

  /**
   * @return mixed
   */
  public function getSiteId() {
    return $this->siteId;
  }

  /**
   * @return mixed
   */
  public function getGenre() {
    return $this->genre;
  }

  /**
   * @param mixed $genre
   *
   * @return $this
   */
  public function setGenre($genre) {
    $this->genre = $genre;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getPackage() {
    return $this->package;
  }

  /**
   * @param mixed $package
   *
   * @return $this
   */
  public function setPackage($package) {
    $this->package = $package;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getSegment() {
    return $this->segment;
  }

  /**
   * @param mixed $segment
   *
   * @return $this
   */
  public function setSegment($segment) {
    $this->segment = $segment;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getCurrentUrl() {
    return $this->currentUrl;
  }

  /**
   * @param mixed $currentUrl
   *
   * @return $this
   */
  public function setCurrentUrl($currentUrl) {
    $this->currentUrl = $currentUrl;
    return $this;
  }

  /**
   * Creates a new comscore information object from configuration.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The comscore_direct configuration.
   *
   * @param string $current_url
   *   The current URL.
   *
   * @return static
   */
  public static function fromConfig(Config $config, $current_url) {
    return new static(
      $config->get('site_id'),
      $config->get('genre'),
      $config->get('package'),
      $config->get('segment'),
      $current_url
    );
  }

}
