<?php

namespace Drupal\preprocess_test\Plugin\Preprocess;

use Drupal\preprocess\PreprocessPluginBase;

/**
 * Test preprocessor provided by module annotation.
 *
 * @Preprocess(
 *   id = "preprocess_test.preprocessor",
 *   hook = "input"
 * )
 */
class ModulePreprocessTestPlugin extends PreprocessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array $variables): array {
    $variables['attributes']['class'][] = 'my-test-input-class';
    return $variables;
  }

}
