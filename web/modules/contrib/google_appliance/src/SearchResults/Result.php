<?php

namespace Drupal\google_appliance\SearchResults;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Markup;

/**
 * Defines a value object representing a search result.
 */
class Result {

  /**
   * Absolute URL.
   *
   * @var string
   */
  protected $absoluteUrl = '';

  /**
   * Encoded URL.
   *
   * @var string
   */
  protected $encodedUrl = '';

  /**
   * Short URL.
   *
   * @var string
   */
  protected $shortUrl = '';

  /**
   * Title.
   *
   * @var string
   */
  protected $title = '';

  /**
   * Snippet.
   *
   * @var string
   */
  protected $snippet = '';

  /**
   * Crawl Date.
   *
   * @var string
   */
  protected $crawlDate = '';

  /**
   * Array of meta values keyed by name.
   *
   * @var string[]
   */
  protected $meta = [];

  /**
   * Mime Type.
   *
   * @var string
   */
  protected $mimeType = '';

  /**
   * Level.
   *
   * @var int
   */
  protected $level = 1;

  /**
   * Constructs a new Result object.
   *
   * @param string $absoluteUrl
   *   Absolute URL.
   * @param string $encodedUrl
   *   Encoded URL.
   * @param string $title
   *   Title.
   * @param string $snippet
   *   Snippet.
   * @param string $crawlDate
   *   Crawl date.
   * @param string $mimeType
   *   Mime Type.
   * @param int $level
   *   Level.
   */
  public function __construct($absoluteUrl, $encodedUrl, $title, $snippet, $crawlDate, $mimeType, $level = 1) {
    $this->absoluteUrl = $absoluteUrl;
    $this->shortUrl = substr($absoluteUrl, 0, 80) . (strlen($absoluteUrl) > 80 ? '...' : '');
    $this->encodedUrl = $encodedUrl;
    $this->title = $title;
    $this->snippet = $snippet;
    $this->crawlDate = $crawlDate;
    $this->mimeType = $mimeType;
    $this->level = $level;
  }

  /**
   * Adds meta.
   *
   * @param string $name
   *   Name.
   * @param string $value
   *   Value.
   *
   * @return $this
   */
  public function addMeta($name, $value) {
    $this->meta[$name] = $value;
    return $this;
  }

  /**
   * Gets value of absoluteUrl.
   *
   * @return string
   *   Value of absoluteUrl
   */
  public function getAbsoluteUrl() {
    return $this->absoluteUrl;
  }

  /**
   * Gets value of encodedUrl.
   *
   * @return string
   *   Value of encodedUrl
   */
  public function getEncodedUrl() {
    return $this->encodedUrl;
  }

  /**
   * Gets value of shortUrl.
   *
   * @return string
   *   Value of shortUrl
   */
  public function getShortUrl() {
    return $this->shortUrl;
  }

  /**
   * Gets value of title.
   *
   * @return string
   *   Value of title
   */
  public function getTitle() {
    return Markup::create(Xss::filter($this->title, ['b', 'i']));
  }

  /**
   * Gets value of snippet.
   *
   * @return string
   *   Value of snippet
   */
  public function getSnippet() {
    return Markup::create(Xss::filter($this->snippet, ['b', 'i']));
  }

  /**
   * Gets value of crawlDate.
   *
   * @return string
   *   Value of crawlDate
   */
  public function getCrawlDate() {
    return $this->crawlDate;
  }

  /**
   * Gets value of meta.
   *
   * @return \string[]
   *   Value of meta
   */
  public function getMeta() {
    return $this->meta;
  }

  /**
   * Gets value of mimeType.
   *
   * @return string
   *   Value of mimeType
   */
  public function getMimeType() {
    return $this->mimeType;
  }

  /**
   * Gets value of level.
   *
   * @return int
   *   Value of level
   */
  public function getLevel() {
    return $this->level;
  }

}
