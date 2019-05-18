<?php

namespace Drupal\markdown\Plugin\Filter;

use Drupal\filter\Plugin\FilterInterface;

/**
 * Interface MarkdownFilterInterface.
 */
interface MarkdownFilterInterface extends FilterInterface {

  /**
   * Retrieves the MarkdownParser plugin for this filter.
   *
   * @return \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface
   *   The MarkdownParser plugin.
   */
  public function getParser();

  /**
   * Retrieves a specific parser setting.
   *
   * @param string $name
   *   The name of the setting to retrieve.
   * @param mixed $default
   *   Optional. The default value to return if not set.
   *
   * @return mixed
   *   The parser setting value.
   */
  public function getParserSetting($name, $default = NULL);

  /**
   * Retrieves all parser specific settings.
   *
   * @return array
   *   The parser settings.
   */
  public function getParserSettings();

  /**
   * Retrieves a specific setting.
   *
   * @param string $name
   *   The name of the setting to retrieve.
   * @param mixed $default
   *   Optional. The default value to return if not set.
   *
   * @return mixed
   *   The setting value.
   */
  public function getSetting($name, $default = NULL);

  /**
   * Retrieves all settings.
   *
   * @return array
   *   The settings.
   */
  public function getSettings();

  /**
   * Indicates whether the filter is enabled or not.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function isEnabled();

}
