<?php

namespace Drupal\google_appliance\SearchResults;

/**
 * Defines a value object for a one-box result set.
 */
class OneBoxResultSet {

  /**
   * Module name.
   *
   * @var string
   */
  protected $moduleName;

  /**
   * Provider.
   *
   * @var string
   */
  protected $provider;

  /**
   * URL text.
   *
   * @var string
   */
  protected $urlText;

  /**
   * URL link.
   *
   * @var string
   */
  protected $urlLink;

  /**
   * Image.
   *
   * @var string
   */
  protected $image;

  /**
   * Description.
   *
   * @var string
   */
  protected $description;

  /**
   * Results.
   *
   * @var \Drupal\google_appliance\SearchResults\OneBoxResult[]
   */
  protected $results = [];

  /**
   * Constructs a new OneBoxResultSet object.
   *
   * @param string $moduleName
   *   Module name.
   * @param string $provider
   *   Provider.
   * @param string $urlText
   *   URL text.
   * @param string $urlLink
   *   URL link.
   * @param string $image
   *   Image.
   * @param string $description
   *   Description.
   */
  public function __construct($moduleName, $provider, $urlText, $urlLink, $image, $description) {
    $this->moduleName = $moduleName;
    $this->provider = $provider;
    $this->urlText = $urlText;
    $this->urlLink = $urlLink;
    $this->image = $image;
    $this->description = $description;
  }

  /**
   * Adds a result.
   *
   * @param \Drupal\google_appliance\SearchResults\OneBoxResult $result
   *   The result.
   *
   * @return $this
   */
  public function addResult(OneBoxResult $result) {
    $this->results[] = $result;
    return $this;
  }

  /**
   * Gets value of moduleName.
   *
   * @return string
   *   Value of moduleName
   */
  public function getModuleName() {
    return $this->moduleName;
  }

  /**
   * Gets value of provider.
   *
   * @return string
   *   Value of provider
   */
  public function getProvider() {
    return $this->provider;
  }

  /**
   * Gets value of urlText.
   *
   * @return string
   *   Value of urlText
   */
  public function getUrlText() {
    return $this->urlText;
  }

  /**
   * Gets value of urlLink.
   *
   * @return string
   *   Value of urlLink
   */
  public function getUrlLink() {
    return $this->urlLink;
  }

  /**
   * Gets value of image.
   *
   * @return string
   *   Value of image
   */
  public function getImage() {
    return $this->image;
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
   * Gets value of results.
   *
   * @return \Drupal\google_appliance\SearchResults\OneBoxResult[]
   *   Value of results
   */
  public function getResults() {
    return $this->results;
  }

}
