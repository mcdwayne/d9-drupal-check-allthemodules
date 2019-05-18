<?php

namespace Drupal\magicblocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a All-Links-Target-Blank block.
 *
 * @Block(
 *   id = "magicblocks_all_links_target_blank",
 *   admin_label = @Translation("All Links Target Blank"),
 *   category = @Translation("Magic Blocks"),
 * )
 */
class AllLinksTargetBlank extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // @see \Drupal\Core\Render\HtmlResponseAttachmentsProcessor::setHeaders
    // @see \Drupal\Core\Render\Element\HtmlTag
    $render['#attached']['html_head'][] = [[
      '#tag' => 'base',
      '#attributes' => [
        'target' => '_blank',
      ],
    ], 'magicblocks_all_links_target_blank'];
    return $render;
  }

}
