<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Interface MarkdownInterface.
 */
interface MarkdownParserInterface extends PluginInspectionInterface {

  /**
   * Converts Markdown into HTML.
   *
   * Note: this method is not guaranteed to be safe from XSS attacks. This
   * returns the raw output from the parser itself. If you need to render
   * this output you should wrap it in a ParsedMarkdown object or use the
   * \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface::parse() method
   * instead.
   *
   * @param string $markdown
   *   The markdown string to convert.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   Optional. The language of the text that is being converted.
   *
   * @return string
   *   The raw parsed HTML returned from the parser.
   *
   * @see \Drupal\markdown\ParsedMarkdownInterface
   * @see \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface::parse()
   */
  public function convertToHtml($markdown, LanguageInterface $language = NULL);

  /**
   * Retrieves a filter format entity.
   *
   * @param string $format
   *   A filter format identifier or entity instance.
   *
   * @return \Drupal\filter\FilterFormatInterface|object
   *   A filter format entity.
   */
  public function getFilterFormat($format = NULL);

  /**
   * Retrieves a short summary of what the MarkdownParser does.
   *
   * @return string|array|null
   *   A render array.
   */
  public function getSummary();

  /**
   * The current version of the parser.
   *
   * @return string
   *   The version.
   */
  public function getVersion();

  /**
   * Displays the human-readable label of the MarkdownParser plugin.
   *
   * @param bool $show_version
   *   Flag indicating whether to show the version with the label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The label.
   */
  public function label($show_version = TRUE);

  /**
   * Loads a cached ParsedMarkdown object.
   *
   * @param string $id
   *   A unique identifier that will be used to cache the parsed markdown.
   * @param string $markdown
   *   Optional. The fallback markdown to parse if the cached ParsedMarkdown
   *   object doesn't yet exist. If provided, it will be parsed
   *   and its identifier set to the provided $id and then cached.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   Optional. The language of the markdown that is being parsed.
   *
   * @return \Drupal\markdown\ParsedMarkdownInterface|null
   *   A ParsedMarkdown object or NULL if it doesn't exist and $markdown was
   *   not provided as a fallback.
   */
  public function load($id, $markdown = NULL, LanguageInterface $language = NULL);

  /**
   * Loads a cached ParsedMarkdown object for a local file system path.
   *
   * @param string $id
   *   A unique identifier that will be used to cache the parsed markdown.
   * @param string $path
   *   The local file system path of a markdown file to parse if the cached
   *   ParsedMarkdown object doesn't yet exist. Once parsed, its identifier
   *   will be set to the provided $id and then cached.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   Optional. The language of the markdown that is being parsed.
   *
   * @return \Drupal\markdown\ParsedMarkdownInterface
   *   A ParsedMarkdown object.
   *
   * @throws \Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException
   *   When the provided $path does not exist in the local file system.
   */
  public function loadPath($id, $path, LanguageInterface $language = NULL);

  /**
   * Loads a cached ParsedMarkdown object for a URL.
   *
   * @param string $id
   *   A unique identifier that will be used to cache the parsed markdown.
   * @param string $url
   *   The external URL of a markdown file to parse if the cached
   *   ParsedMarkdown object doesn't yet exist. Once parsed, its identifier
   *   will be set to the provided $id and then cached.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   Optional. The language of the markdown that is being parsed.
   *
   * @return \Drupal\markdown\ParsedMarkdownInterface
   *   A ParsedMarkdown object.
   *
   * @throws \Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException
   *   When the provided $url does not exist or is not reachable.
   */
  public function loadUrl($id, $url, LanguageInterface $language = NULL);

  /**
   * Parses markdown into HTML.
   *
   * @param string $markdown
   *   The markdown string to parse.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   Optional. The language of the markdown that is being parsed.
   *
   * @return \Drupal\markdown\ParsedMarkdownInterface
   *   A safe ParsedMarkdown object.
   *
   * @see \Drupal\markdown\ParsedMarkdownInterface
   * @see \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface::convertToHtml()
   */
  public function parse($markdown, LanguageInterface $language = NULL);

  /**
   * Parses markdown from a local file into HTML.
   *
   * @param string $path
   *   A filesystem path of a markdown file to parse.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   Optional. The language of the markdown that is being parsed.
   *
   * @return \Drupal\markdown\ParsedMarkdownInterface
   *   A ParsedMarkdownInterface object.
   *
   * @throws \Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException
   *   When the provided $path does not exist in the local file system.
   */
  public function parsePath($path, LanguageInterface $language = NULL);

  /**
   * Parses markdown from an external URL into HTML.
   *
   * @param string|\Drupal\Core\Url|\Psr\Http\Message\UriInterface $url
   *   An external URL of a markdown file to parse.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   Optional. The language of the markdown that is being parsed.
   *
   * @return \Drupal\markdown\ParsedMarkdownInterface
   *   A ParsedMarkdownInterface object.
   *
   * @throws \Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException
   *   When the provided $url does not exist or is not reachable.
   */
  public function parseUrl($url, LanguageInterface $language = NULL);

  /**
   * Generates a filter's tip.
   *
   * A filter's tips should be informative and to the point. Short tips are
   * preferably one-liners.
   *
   * @param bool $long
   *   Whether this callback should return a short tip to display in a form
   *   (FALSE), or whether a more elaborate filter tips should be returned for
   *   template_preprocess_filter_tips() (TRUE).
   *
   * @return string|null
   *   Translated text to display as a tip, or NULL if this filter has no tip.
   */
  public function tips($long = FALSE);

}
