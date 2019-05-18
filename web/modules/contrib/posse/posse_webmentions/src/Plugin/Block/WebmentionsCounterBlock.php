<?php

namespace Drupal\hello_world\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Hello' Block.
 *
 * @Block(
 *   id = "webmentions_counter",
 *   admin_label = @Translation("Webmentions Counter"),
 *   category = @Translation("POSSE"),
 * )
 */
class WebmentionsCounterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $url = (!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] : "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
    return [
      '#theme' => 'webmentions_counter',
      '#url' => $url,
      '#attached' => [
        'library' => [ 'posse_webmentions/webmentionsio' ]
      ]
    ];
  }

}
