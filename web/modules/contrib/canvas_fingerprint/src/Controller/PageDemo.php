<?php

namespace Drupal\canvas_fingerprint\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for page example routes.
 */
class PageDemo extends ControllerBase {

  /**
   * Constructs a simple page.
   */
  public function page() {
    $output = [
      '#prefix' => "<div class='container' id='clrs'><div class='row'>",
      '#suffix' => "</div></div>
      <ul>
        <li><a href='https://browserleaks.com/canvas#how-does-it-work'>About (browserleaks.com)</a></li>
        <li><a href='https://github.com/Valve/fingerprintJS'>github.com/Valve/fingerprintJS</a></li>
        <li><a href='http://cseweb.ucsd.edu/~hovav/dist/canvas.pdf'>cseweb.ucsd.edu/~hovav/dist/canvas.pdf</a></li>
      </ul>

      <div id='fingerprint'></div>
      <div id='fingerimage'></div>
      <div id='fingerdata'></div>",
    ];
    return [
      'colors' => $output,
      'simple' => [
        '#markup' => '<p>Simple lazy dog.</p>',
        '#attached' => [
          'library' => ['canvas_fingerprint/init'],
        ],
      ],
    ];
  }

}
