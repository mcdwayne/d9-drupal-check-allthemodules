<?php

namespace Drupal\zchat\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Z Chat' Block.
 *
 * @Block(
 *   id = "zchat_block",
 *   admin_label = @Translation("Z Chat block"),
 *   category = @Translation("Z Chat"),
 * )
 */
class ZchatBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $user = \Drupal::currentUser();
    $config = \Drupal::config('zchat.settings');
    if ($user->hasPermission('use zchat')) {
      $output = [
        '#type' => 'markup',
        '#markup' => '<div id="zchat_content"></div>',
        '#attached' => [
          'library' => [
            'zchat/zchat_js',
          ],
          'drupalSettings' => [
            'path' => [
              'getHost' => \Drupal::request()->getSchemeAndHttpHost(),
            ],
            'zchat' => [
              'zchat_message_refresh_interval' => $config->get('zchat_message_refresh_interval'),
              'zchat_load_more_offeset' => $config->get('zchat_load_more_offeset'),
            ],
          ],
        ],
      ];

      if ($config->get('zchat_include_default_css')) {
        $output['#attached']['library'][] = 'zchat/zchat_default_css';
      }
      if ($config->get('zchat_include_style_css')) {
        $output['#attached']['library'][] = 'zchat/zchat_style_css';
      }
    }
    else {
      $output = [
        '#type' => 'markup',
        '#markup' => '',
      ];
    }
    return $output;
  }

}
