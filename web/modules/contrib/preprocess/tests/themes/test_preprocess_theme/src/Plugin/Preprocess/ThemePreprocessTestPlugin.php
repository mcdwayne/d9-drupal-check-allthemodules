<?php

namespace Drupal\test_preprocess_theme\Plugin\Preprocess;

use Drupal\preprocess\PreprocessPluginBase;

/**
 * Test preprocessor provided by theme.
 */
class ThemePreprocessTestPlugin extends PreprocessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array $variables): array {
    $variables['attributes']['class'][] = 'my-test-image-class';
    return $variables;
  }

}
