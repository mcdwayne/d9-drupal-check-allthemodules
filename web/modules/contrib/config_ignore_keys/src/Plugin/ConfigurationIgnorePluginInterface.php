<?php

namespace Drupal\config_ignore_keys\Plugin;

/**
 * Interface ConfigurationIgnorePluginInterface.
 *
 * @package Drupal\config_ignore_keys\Plugin
 */
interface ConfigurationIgnorePluginInterface {

  /**
   * Should return an array containing the following structure.
   *
   * @code
   *  [
   *    'name.of.the.configuration.file.without.extension.1' => [
   *      'key.of.the.configuration.1'
   *      'key.of.the.configuration.2'
   *    ],
   *    'name.of.the.configuration.file.without.extension.2' => [
   *      'key.of.the.configuration.1'
   *      'key.of.the.configuration.2'
   *    ],
   *    'name.of.the.configuration.file.without.extension.3'
   * ]
   * @endcode
   *
   * The key.of.the.configuration is the path to the config key inside the array
   * containing the entire configuration of the file.
   *
   * For 'name.of.the.configuration.file.without.extension.3' the whole file
   * will be ignored.
   *
   * @see ConfigFactoryInterface::getEditable()
   *
   * @return array
   *   The configuration.
   */
  public function getConfigurations();

}
