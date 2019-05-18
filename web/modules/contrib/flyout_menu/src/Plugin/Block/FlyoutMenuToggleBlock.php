<?php

namespace Drupal\flyout_menu\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides the menu toggle icon.
 *
 * @Block(
 *   id = "flyout_menu_toggle",
 *   admin_label = @Translation("Flyout menu toggle icon"),
 *   category = @Translation("Menus")
 * )
 */
class FlyoutMenuToggleBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'flyout_menu_toggle',
      '#attached' => [
        'library' => [
          'flyout_menu/toggle',
        ],
      ],
    ];
  }

}
