<?php

namespace Drupal\ibm_watson\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for page example routes.
 */
class WatsonExampleController extends ControllerBase {

  /**
   * Example function page.
   */
  public function description() {
    // Make our links. First the simple page.
    $page_example_simple_link = 'page_example_simple_link';

    // Now the arguments page.
    $arguments_url = 'arguments_url';
    $page_example_arguments_link = 'page_example_arguments_link';

    // Assemble the markup.
    $build = [
      '#markup' => $this->t('<p>The Page example module provides two pages, "simple" and "arguments".</p><p>The @simple_link just returns a renderable array for display.</p><p>The @arguments_link takes two arguments and displays them, as in @arguments_url</p>',
        [
          '@simple_link' => $page_example_simple_link,
          '@arguments_link' => $page_example_arguments_link,
          '@arguments_url' => $arguments_url,
        ]
      ),
    ];

    return $build;
  }

}
