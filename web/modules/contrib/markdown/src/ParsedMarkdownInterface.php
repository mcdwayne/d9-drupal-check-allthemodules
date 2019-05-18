<?php

namespace Drupal\markdown;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageInterface;

interface ParsedMarkdownInterface extends MarkupInterface, \Countable, \Serializable {

  /**
   * Indicates the item should never be removed unless explicitly deleted.
   */
  const PERMANENT = CacheBackendInterface::CACHE_PERMANENT;

  /**
   * Creates new ParsedMarkdown object.
   *
   * @param string $markdown
   *   The raw markdown.
   * @param string $html
   *   The parsed HTML from $markdown.
   * @param bool $xss_safe
   *   Flag indicating whether the parsed HTML is safe from XSS vulnerabilities.
   * @param \Drupal\Core\Language\LanguageInterface|NULL $language
   *   The language of the parsed markdown, if known.
   *
   * @return static
   */
  public static function create($markdown = '', $html = '', $xss_safe = FALSE, LanguageInterface $language = NULL);

  /**
   * Loads a cached ParsedMarkdown object.
   *
   * @param string $id
   *   A unique identifier.
   *
   * @return static|null
   *   A cached ParsedMarkdown object or NULL if it doesn't exist.
   */
  public static function load($id);

  /**
   * Normalizes markdown.
   *
   * @param string $markdown
   *
   * @return string
   *   The normalized markdown.
   */
  public static function normalizeMarkdown($markdown);

  /**
   * Retrieves the UNIX timestamp for when this object should expire.
   *
   * Note: this method should handle the use case of a string being set to
   * indicate a relative future time.
   *
   * @param int $from_time
   *   A UNIX timestamp used to expire from. This will only be used when the
   *   expire value has been set to a relative time in the future, e.g. day,
   *   week, month, etc. If not set, this current request time will be used.
   *
   * @return int
   *   The UNIX timestamp.
   */
  public function getExpire($from_time = NULL);

  /**
   * Retrieves the parsed HTML.
   *
   * @return string
   *   The parsed HTML.
   */
  public function getHtml();

  /**
   * Retrieves the identifier for this object.
   *
   * Note: if no identifier is currently set, a unique hash based on the
   * contents of the parsed HTML will be used.
   *
   * @return string
   *   The identifier for this object.
   */
  public function getId();

  /**
   * Retrieves the human-readable label for this object.
   *
   * Note: if no label is currently set, the identifier for the object is
   * returned instead.
   *
   * @return string
   *   The label for this object.
   */
  public function getLabel();

  /**
   * Retrieves the raw markdown source.
   *
   * @return string
   *   The markdown source.
   */
  public function getMarkdown();

  /**
   * Retrieves the file size of the parsed HTML.
   *
   * @param bool $formatted
   *   Flag indicating whether to retrieve the formatted, human-readable,
   *   file size.
   * @param int $decimals
   *   The number of decimal points to use if $formatted is TRUE.
   *
   * @return int|string
   *   The raw file size in bytes or the formatted human-readable file size.
   */
  public function getSize($formatted = FALSE, $decimals = 2);

  /**
   * Compares whether the provided markdown matches this object.
   *
   * @param string|\Drupal\markdown\ParsedMarkdownInterface $markdown
   *   An external markdown source.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function matches($markdown);

  /**
   * Caches a ParsedMarkdown object.
   *
   * @return static
   */
  public function save();

  /**
   * Sets the object's expiration timestamp.
   *
   * @param int|string $expire
   *   A UNIX timestamp or a string indicating a relative time in the future of
   *   when this object is to expire, e.g. "1+ day".
   *
   * @return static
   */
  public function setExpire($expire = Cache::PERMANENT);

  /**
   * Sets the object's identifier.
   *
   * @param string $id
   *   An identifier.
   *
   * @return static
   */
  public function setId($id);

  /**
   * Sets the object's label.
   *
   * @param string $label
   *   A human-readable label.
   *
   * @return static
   */
  public function setLabel($label);

}
