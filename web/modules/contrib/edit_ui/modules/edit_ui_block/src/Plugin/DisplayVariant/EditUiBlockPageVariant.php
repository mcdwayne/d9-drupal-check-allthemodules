<?php

/**
 * @file
 * Contains \Drupal\edit_ui_block\Plugin\DisplayVariant\EditUiBlockPageVariant.
 */

namespace Drupal\edit_ui_block\Plugin\DisplayVariant;

use Drupal\block\Plugin\DisplayVariant\BlockPageVariant;

/**
 * Provides a page display variant that add decorations for edit_ui_block interface.
 *
 * @PageDisplayVariant(
 *   id = "edit_ui_block_page",
 *   admin_label = @Translation("Page with all regions and blocks")
 * )
 */
class EditUiBlockPageVariant extends BlockPageVariant {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();
    $theme = \Drupal::config('system.theme')->get('default');
    $visible_regions = system_region_list($theme, REGIONS_VISIBLE);

    foreach (array_keys($visible_regions) as $region) {
      if (empty($build[$region])) {
        $build[$region] = [];
      }

      // Add edit_ui_block region block at the beginning of each visible regions.
      $edit_ui_block_region_block = array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array('edit-ui__region-block', 'js-edit-ui__region-block'),
        ),
        'label' => array(
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $visible_regions[$region],
          '#attributes' => array(
            'class' => array('edit-ui__region-label'),
          ),
        ),
      );
      array_unshift($build[$region], $edit_ui_block_region_block);

      // Add edit_ui_block filling block at the end of each visible regions.
      $edit_ui_block_filling_block = array(
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => array(
          'class' => array('edit-ui__region-placeholder'),
        ),
      );
      array_push($build[$region], $edit_ui_block_filling_block);
    }

    return $build;
  }

}
