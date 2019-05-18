<?php

namespace Drupal\responsive_menu_combined\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a site footer Block.
 *
 * @Block(
 *   id = "responsive_menu_combined_block",
 *   admin_label = @Translation("Responsive menu combined block"),
 * )
 */
class ResponsiveMenuCombinedBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Get the config from responsive menu combined.
    $config = \Drupal::config('responsive_menu_combined.settings');

    // Set the variable for the menus in responsive menu combined.
    $set_menus = $config->get('menus');

    // Get the display parent title config value.
    $display_parent_title = $config->get('display_parent_title');

    // Get the html tags config value.
    $html_tags = $config->get('html_tags');

    // Step through each menu and set the id and title.
    $counter = 0;
    foreach ($set_menus as $menu_id => $set_menu) {
      $content['menus'][$counter]['menu_id'] = $menu_id;
      $content['menus'][$counter]['menu_title'] = $set_menu;
      $counter++;
    }

    $content['display_parent_title'] = $display_parent_title;
    $content['html_tags'] = $html_tags;

    // Return the menus.
    return $content;
  }

}
