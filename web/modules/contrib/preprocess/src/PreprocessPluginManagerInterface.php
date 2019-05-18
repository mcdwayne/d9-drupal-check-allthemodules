<?php

namespace Drupal\preprocess;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Interface definition for managing preprocess plugins.
 *
 * @package Drupal\preprocess
 */
interface PreprocessPluginManagerInterface extends PluginManagerInterface {

  /**
   * Get preprocessors for a given hook.
   *
   * @param string $hook
   *   The name of the hook.
   *
   * @return \Drupal\preprocess\PreprocessInterface[]
   *   Array of preprocessors.
   */
  public function getPreprocessors(string $hook): array;

  /**
   * Checks whether there are any preprocessors for the request at that time.
   *
   * @return bool
   *   Whether there are preprocessors.
   */
  public function hasPreprocessors(): bool;

}
