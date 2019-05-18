<?php

/**
 * @file
 * Contains \Drupal\headbar\Plugin\Block\HeadbarBlock.
 */

namespace Drupal\headbar\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Headbar' block.
 *
 * @Block(
 *   id = "headbar_block",
 *   admin_label = @Translation("Headbar")
 * )
 */
class HeadbarBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Check if current user has permission to view headbar.
    $current_user = \Drupal::currentUser();
    $has_permission = $current_user->hasPermission('view headbar');
    if ($has_permission) {
      $build = [];
      // Get configuration values from headbar config form.
      $config = \Drupal::config('headbar.configure');
      $colour = $config->get('msg_color');
      $color_hover = $config->get('msg_color_hover');
      $delaytime = $config->get('msg_delaytime');
      // Add/Attach JS file.
      $build['#attached']['library'][] = 'headbar/headbar.script';
      // Pass Settings to JS file.
      $build['#attached']['drupalSettings']['headbar']['colour'] = $colour;
      $build['#attached']['drupalSettings']['headbar']['color_hover'] = $color_hover;
      $build['#attached']['drupalSettings']['headbar']['delaytime'] = $delaytime;
      // Add/Attach CSS file.
      $build['#attached']['library'][] = 'headbar/headbar.theme';
      // Attach template file using #theme.
      $build['#theme'] = ['headbar_block'];
      // Finally return the array.
      return $build;
    }
    else {
      return [];
    }
  }
}
