<?php

namespace Drupal\chunker\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Utility\Xss;

/**
 * Controller routines for displaying examples.
 *
 * This is not a default label left over from scaffolding or anything,
 * it's actually about displaying examples of layouts!
 *
 * Serves pages at
 * /admin/help/chunker/examples
 * /admin/help/chunker/examples/{method}
 */
class ExampleController extends ControllerBase {

  /**
   * Prints a page listing various methods.
   *
   * As seen at /admin/help/chunker/examples .
   *
   * @return array
   *   A render array for the help page.
   */
  public function exampleIndex() {
    $output = [];
    $methods = chunker_method_load();
    foreach ($methods as $method_id => $method) {
      $url = Url::fromRoute('chunker.help.example', ['name' => $method_id]);
      $this_output = [
        '#prefix' => '<dl>',
        '#suffix' => '</dl>',
        [
          '#markup' => Link::fromTextAndUrl($method->name, $url)->toString(),
          '#prefix' => '<dt>',
          '#suffix' => '</dt>',
        ],
        [
          '#markup' => $method->description,
          '#prefix' => '<dd>',
          '#suffix' => '</dd>',
        ],
      ];
      $output[$method_id] = $this_output;
    }

    $plugins = chunker_plugin_load();
    foreach ($plugins as $method_id => $plugin) {
      $url = Url::fromRoute('chunker.help.example', ['name' => $method_id]);
      $this_output = [
        '#prefix' => '<dl>',
        '#suffix' => '</dl>',
        [
          '#markup' => Link::fromTextAndUrl($plugin['label'], $url)->toString(),
          '#prefix' => '<dt>',
          '#suffix' => '</dt>',
        ],
        [
          '#markup' => $plugin['description'],
          '#prefix' => '<dd>',
          '#suffix' => '</dd>',
        ],
      ];
      $output[$method_id] = $this_output;
    }

    return $output;
  }

  /**
   * Prints a page displaying the chunker in action.
   *
   * @param string $name
   *   Name of the method.
   *
   * @return array
   *   A render array as expected by drupal_render().
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function examplePage($name) {
    $method_id = $name;
    $chunker_method = chunker_method_load($method_id);
    if (empty($chunker_method)) {
      throw new NotFoundHttpException();
    }

    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'chunker chunker-' . $method_id . ' section',
      ],
    ];

    // Load the test html.
    $help_dir = drupal_get_path('module', 'chunker') . '/help';
    $path = $help_dir . '/sample_page.html';
    $text = file_get_contents($path);

    // Each method will probably introduce some settings to pass through.
    $settings = array_merge(chunker_default_settings(), isset($chunker_method->settings) ? $chunker_method->settings : []);

    // Apply the chunker to the text.
    $build[] = [
      '#markup' => chunker_chunk_text($text, $settings),
      // Allow fieldset and legend through when rendering.
      // They usually get sanitized out.
      '#allowed_tags' => array_merge(['fieldset', 'legend'], Xss::getAdminTagList()),
    ];

    // Add the javascript or css.
    if (isset($chunker_method->attached)) {
      $build['#attached'] = $chunker_method->attached;
    }
    if ($this->config('chunker.settings')->get('chunker_debuglevel')) {
      // Add some sketchy markup to the page to illustrate chunker.
      $build['#attached']['library'][] = "chunker/chunker.debug";
    }
    return $build;
  }

}
