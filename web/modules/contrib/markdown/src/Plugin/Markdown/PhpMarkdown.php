<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Core\Language\LanguageInterface;
use Drupal\markdown\ParsedMarkdown;
use Drupal\markdown\Traits\MarkdownParserBenchmarkTrait;
use Michelf\MarkdownExtra;

/**
 * Class PhpMarkdown.
 *
 * @MarkdownParser(
 *   id = "michelf/php-markdown",
 *   label = @Translation("michelf/php-markdown"),
 *   checkClass = "Michelf\MarkdownExtra",
 * )
 */
class PhpMarkdown extends BaseMarkdownParser implements MarkdownParserBenchmarkInterface {

  use MarkdownParserBenchmarkTrait;

  /**
   * MarkdownExtra parsers, keyed by filter identifier.
   *
   * @var \Michelf\MarkdownExtra[]
   */
  protected static $parsers = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->getParser();
  }

  /**
   * {@inheritdoc}
   */
  public function convertToHtml($markdown, LanguageInterface $language = NULL) {
    return $this->getParser()->transform($markdown);
  }

  /**
   * Retrieves the PHP Markdown parser.
   *
   * @return \Michelf\MarkdownExtra
   *   A PHP Markdown parser.
   */
  public function getParser() {
    if (!isset(static::$parsers[$this->filterId])) {
      $parser = new MarkdownExtra();
      if ($this->filter) {
        foreach ($this->settings as $name => $value) {
          $parser->$name = $value;
        }
      }
      static::$parsers[$this->filterId] = $parser;
    }
    return static::$parsers[$this->filterId];
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return MarkdownExtra::MARKDOWNLIB_VERSION;
  }

}
