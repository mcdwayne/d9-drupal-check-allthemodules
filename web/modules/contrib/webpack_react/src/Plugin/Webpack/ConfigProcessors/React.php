<?php

namespace Drupal\webpack_react\Plugin\Webpack\ConfigProcessors;

use Drupal\webpack\Plugin\Webpack\ConfigProcessors\ConfigProcessorBase;

/**
 * Adds react loader to webpack.
 *
 * @WebpackConfigProcessor(
 *   id = "react",
 *   weight = -1,
 * )
 */
class React extends ConfigProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function processConfig(&$config, $context) {
    foreach ($config['module']['rules'] as &$rule) {
      if ($rule['test'] === '`/.js$/`') {
        // This rule is added by the webpack_babel module.
        $rule['use'][0]['options']['presets'][] = '@babel/preset-react';
      }
    }
  }

}
