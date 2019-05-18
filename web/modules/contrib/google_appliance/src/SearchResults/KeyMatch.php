<?php

namespace Drupal\google_appliance\SearchResults;

/**
 * Defines a value object for key-matches.
 */
class KeyMatch {

  /**
   * Description.
   *
   * @var string
   */
  protected $description;

  /**
   * URL.
   *
   * @var string
   */
  protected $url;

  /**
   * Constructs a new KeyMatch object.
   *
   * @param string $description
   *   Description.
   * @param string $url
   *   URL.
   */
  public function __construct($description, $url) {
    $this->description = $description;
    $this->url = $url;
  }

  /**
   * Gets value of description.
   *
   * @return string
   *   Value of description
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Gets value of url.
   *
   * @return string
   *   Value of url
   */
  public function getUrl() {
    return $this->url;
  }

}
