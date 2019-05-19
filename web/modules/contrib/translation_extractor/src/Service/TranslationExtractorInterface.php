<?php

namespace Drupal\translation_extractor\Service;

use Drupal\Core\Config\ImmutableConfig;

/**
 * Interface TranslationExtractorInterface.
 *
 * Defines the API of the extraction service.
 *
 * @package Drupal\translation_extractor\Service
 */
interface TranslationExtractorInterface {

  /**
   * Scans the given module according to the settings configured.
   *
   * @param ImmutableConfig $configuration
   *   The module settings.
   * @param string $module
   *   The module to scan.
   */
  public function scan(ImmutableConfig $configuration, $module);

  /**
   * Displays the scan results.
   *
   * @return array
   *   The render array containing the results page.
   */
  public function scanResults();

}
