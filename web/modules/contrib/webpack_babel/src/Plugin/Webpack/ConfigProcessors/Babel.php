<?php

namespace Drupal\webpack_babel\Plugin\Webpack\ConfigProcessors;

use Drupal\webpack\Plugin\Webpack\ConfigProcessors\ConfigProcessorBase;

/**
 * Adds babel to webpack, so the files written in modern js are transpiled.
 *
 * @WebpackConfigProcessor(
 *   id = "babel",
 *   weight = -4,
 * )
 */
class Babel extends ConfigProcessorBase {

  // TODO: Add a way to configure supported browsers via config.

  /**
   * {@inheritdoc}
   */
  public function processConfig(&$config, $context) {
    $config['module']['rules'][] = [
      'test' => '`/.js$/`',
      'exclude' => '`/(node_modules|bower_components)/`',
      'use' => [
        [
          'loader' => 'babel-loader',
          'options' => [
            'presets' => ['@babel/env'],
          ],
        ]
      ],
    ];
  }

}
