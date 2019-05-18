<?php

namespace Drupal\search_365\SearchResults;

/**
 * Defines a value object representing a search result.
 */
class Result {

  /**
   * Factory method for creating a new Result.
   *
   * @return self
   *   Returns static object.
   */
  public static function create() {
    return new static();
  }

  /**
   * Search title.
   *
   * @var string
   */
  protected $title;

  /**
   * Zulu time date/time.
   *
   * @var string
   */
  protected $crawlTime;

  /**
   * URL of the search result.
   *
   * @var string
   */
  protected $url;

  /**
   * Text of the result.
   *
   * @var string
   */
  protected $body;

  /**
   * Collection name.
   *
   * @var string
   */
  protected $collection;

  /**
   * System title.
   *
   * @var string
   */
  protected $systemTitle;

  /**
   * Gets the Title.
   *
   * @return string
   *   The Title.
   */
  public function getTitle(): string {
    return $this->title;
  }

  /**
   * Sets the Title.
   *
   * @param string $title
   *   The Title.
   *
   * @return $this
   */
  public function setTitle(string $title): Result {
    $this->title = $title;
    return $this;
  }

  /**
   * Gets the CrawlTime.
   *
   * @return string
   *   The CrawlTime.
   */
  public function getCrawlTime(): string {
    return $this->crawlTime;
  }

  /**
   * Sets the CrawlTime.
   *
   * @param string $crawlTime
   *   The CrawlTime.
   *
   * @return $this
   */
  public function setCrawlTime(string $crawlTime): Result {
    $this->crawlTime = $crawlTime;
    return $this;
  }

  /**
   * Gets the Url.
   *
   * @return string
   *   The Url.
   */
  public function getUrl(): string {
    return $this->url;
  }

  /**
   * Sets the Url.
   *
   * @param string $url
   *   The Url.
   *
   * @return $this
   */
  public function setUrl(string $url): Result {
    $this->url = $url;
    return $this;
  }

  /**
   * Gets the Body.
   *
   * @return string
   *   The Body.
   */
  public function getBody(): string {
    return $this->body;
  }

  /**
   * Sets the Body.
   *
   * @param string $body
   *   The Body.
   *
   * @return $this
   */
  public function setBody(string $body): Result {
    $this->body = $body;
    return $this;
  }

  /**
   * Gets the Collection.
   *
   * @return string
   *   The Collection.
   */
  public function getCollection(): string {
    return $this->collection;
  }

  /**
   * Sets the Collection.
   *
   * @param string $collection
   *   The Collection.
   *
   * @return $this
   */
  public function setCollection(string $collection): Result {
    $this->collection = $collection;
    return $this;
  }

  /**
   * Gets the SystemTitle.
   *
   * @return string
   *   The SystemTitle.
   */
  public function getSystemTitle(): string {
    return $this->systemTitle;
  }

  /**
   * Sets the SystemTitle.
   *
   * @param string $systemTitle
   *   The SystemTitle.
   *
   * @return $this
   */
  public function setSystemTitle(string $systemTitle): Result {
    $this->systemTitle = $systemTitle;
    return $this;
  }

}
