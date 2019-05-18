<?php

namespace Drupal\authorization_code\Exceptions;

/**
 * Broken / missing plugin exception.
 */
class BrokenPluginException extends \Exception {

  /**
   * BrokenPluginException constructor.
   *
   * @param string $plugin_type
   *   The plugin type.
   * @param array $configuration
   *   The plugin configuration.
   * @param \Throwable|null $previous
   *   The previous exception.
   */
  public function __construct(string $plugin_type, array $configuration, \Throwable $previous = NULL) {
    parent::__construct(
      sprintf('The %s plugin failed to be initiated with the following configuration: %s',
        $plugin_type, print_r($configuration, TRUE)),
      0, $previous);
  }

}
