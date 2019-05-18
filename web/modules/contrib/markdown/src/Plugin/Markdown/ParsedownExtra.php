<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Core\Language\LanguageInterface;
use Drupal\markdown\Traits\MarkdownParserBenchmarkTrait;

/**
 * Class ParsedownExtra.
 *
 * @MarkdownParser(
 *   id = "erusev/parsedown-extra",
 *   label = @Translation("erusev/parsedown-extra"),
 *   checkClass = "ParsedownExtra",
 * )
 */
class ParsedownExtra extends BaseMarkdownParser implements MarkdownParserBenchmarkInterface {

  use MarkdownParserBenchmarkTrait;

  /**
   * MarkdownExtra parsers, keyed by filter identifier.
   *
   * @var \ParsedownExtra[]
   */
  protected static $parsers = [];

  /**
   * A map of setting <-> method.
   *
   * @var array
   */
  protected static $settingsMethodMap = [
    'breaks_enabled' => 'setBreaksEnabled',
    'markup_escaped' => 'setMarkupEscaped',
    'safe_mode' => 'setSafeMode',
    'urls_linked' => 'setUrlsLinked',
  ];

  /**
   * {@inheritdoc}
   */
  public function convertToHtml($markdown, LanguageInterface $language = NULL) {
    return $this->getParser()->text($markdown);
  }

  /**
   * Retrieves the PHP Markdown parser.
   *
   * @return \ParsedownExtra
   *   A PHP Markdown parser.
   */
  public function getParser() {
    if (!isset(static::$parsers[$this->filterId])) {
      $parser = new \ParsedownExtra();
      if ($this->filter) {
        foreach ($this->settings as $name => $value) {
          if ($method = $this->getSettingMethod($name)) {
            $parser->$method($value);
          }
        }
      }
      static::$parsers[$this->filterId] = $parser;
    }
    return static::$parsers[$this->filterId];
  }

  /**
   * Retrieves the method used to configure a specific setting.
   *
   * @param string $name
   *   The name of the setting.
   *
   * @return string|null
   *   The method name or NULL if method does not exist.
   */
  protected function getSettingMethod($name) {
    return isset(static::$settingsMethodMap[$name]) ? static::$settingsMethodMap[$name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return \Parsedown::version . '/' . \ParsedownExtra::version;
  }

}
