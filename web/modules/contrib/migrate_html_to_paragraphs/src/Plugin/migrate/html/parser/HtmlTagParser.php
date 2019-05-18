<?php

namespace Drupal\migrate_html_to_paragraphs\Plugin\migrate\html\parser;

use Drupal\Core\Plugin\PluginBase;

/**
 * Abstract class for HTML Tag parsers.
 */
abstract class HtmlTagParser extends PluginBase {

  /**
   * The pattern we are searching for in the text.
   *
   * @var string
   */
  private $pattern;

  /**
   * The found tags and their parsed data.
   *
   * @var array
   */
  private $tags = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setPattern($this->definePattern());
  }

  /**
   * Returns the corresponding HTML Tag processor plugin ID.
   *
   * @return string|null
   *   The processor plugin ID this parser corresponds to or NULL if none.
   */
  abstract protected function getCorrespondingProcessorPluginId();

  /**
   * Use to define the pattern of the HTML Tag parser.
   *
   * @return string
   *   The pattern string.
   */
  abstract protected function definePattern();

  /**
   * Parser to parse all the identified strings.
   *
   * @param string $text
   *   The text we need to parse.
   *
   * @return int
   *   The number of found tags.
   */
  public function parse($text) {
    $matches = [];
    if (!preg_match_all($this->getPattern(), $text, $matches, PREG_OFFSET_CAPTURE)) {
      return 0;
    }

    foreach ($matches[0] as $match) {
      list($tag, $position) = $match;
      $data = $this->parseTag($tag);
      if (!$data) {
        continue;
      }
      $data['position'] = $position;
      $data['_processor_plugin_id'] = $this->getCorrespondingProcessorPluginId();
      $this->addTag($position, $data);
    }

    return count($this->getTags());
  }

  /**
   * Parse a single tag into a data array.
   *
   * @param string $tag
   *   The tag to parse.
   *
   * @return array
   *   The parsed data.
   */
  abstract protected function parseTag($tag);

  /**
   * Get all parsed tags.
   *
   * @return array
   *   The tags found in the content.
   */
  public function getTags() {
    return $this->tags;
  }

  /**
   * Add a single tag and the parsed data to the found array.
   *
   * @param int $position
   *   Position of the parsed tag.
   * @param array $data
   *   Parsed data for the tag.
   */
  private function addTag($position, array $data) {
    $this->tags[$position] = $data;
  }

  /**
   * Set the regular expression pattern to search for.
   *
   * @param string $pattern
   *   Regular expression.
   */
  private function setPattern($pattern) {
    $this->pattern = $pattern;
  }

  /**
   * Get the regular expression pattern to search for.
   *
   * @return string
   *   Regular expression.
   */
  private function getPattern() {
    return $this->pattern;
  }

  /**
   * Helper to parse a single element from the tag by the given pattern.
   *
   * @param string $tag
   *   The source HTML tag.
   * @param string $pattern
   *   The pattern to parse the value from the tag.
   *
   * @return string|null
   *   Return the match, NULL if not found.
   */
  protected function parseTagByPattern($tag, $pattern) {
    if (preg_match($pattern, $tag, $result)) {
      return $result[1];
    }
    return NULL;
  }

}
