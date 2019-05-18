<?php

/**
 * @file
 * Contains \Drupal\nivo_slider\Plugin\block\block\NivoSliderSliderBlock.
 */

namespace Drupal\nivo_slider\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;

/**
 * Provides a 'Slider' block.
 *
 * @Block(
 *   id = "nivo_slider_slider_block",
 *   admin_label = @Translation("Slider"),
 *   category = "nivo_slider"
 * )
 */
class NivoSliderSliderBlock extends BlockBase {

  /**
   * Overrides \Drupal\block\BlockBase::blockAccess().
   */
//  public function blockAccess() {
//    return user_access('access content');
//  }

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    // Get the current slider settings.
    $config = \Drupal::getContainer()->get('config.factory')->getEditable('nivo_slider.settings');
    $build = [
      '#type' => 'markup',
      '#theme' => 'nivo_slider_wrapper',
      '#attached' => [
        'drupalSettings' => [
          'nivo_slider' => [
            'effect' => $config->get('options.effect') ? Html::escape($config->get('options.effect')) : 'random',
            'slices' => $config->get('options.slices') ? (int) Html::escape($config->get('options.slices')) : 15,
            'boxCols' => $config->get('options.box_columns')? (int) Html::escape($config->get('options.box_columns')) : 8,
            'boxRows' => $config->get('options.box_rows') ? (int) Html::escape($config->get('options.box_rows')) : 4,
            'animSpeed' => $config->get('options.animation_speed') ? (int) Html::escape($config->get('options.animation_speed')) : 500,
            'pauseTime' => $config->get('options.pause_time') ? (int) Html::escape($config->get('options.pause_time')) : 3000,
            'startSlide' => $config->get('options.start_slide') ? (int) Html::escape($config->get('options.start_slide')) : 0,
            'directionNav' => Html::escape($config->get('options.directional_navigation')) == 1 ? TRUE : FALSE,
            'controlNav' => Html::escape($config->get('options.control_navigation')) == 1 ? TRUE : FALSE,
            'controlNavThumbs' => Html::escape($config->get('options.control_nav_thumbs')) == 1 ? TRUE : FALSE,
            'pauseOnHover' => Html::escape($config->get('options.pause_on_hover')) == 1 ? TRUE : FALSE,
            'manualAdvance' => Html::escape($config->get('options.manual_advance')) == 1 ? TRUE : FALSE,
            'prevText' => $config->get('options.previous_text') ? Html::escape($config->get('options.previous_text')) : t('Prev')->render(),
            'nextText' => $config->get('options.next_text') ? Html::escape($config->get('options.next_text')) : t('Next')->render(),
            'randomStart' => Html::escape($config->get('options.random_start')) == 1 ? TRUE : FALSE,
          ],
        ],
        'library' => [
          'nivo_slider/nivo.slider'
        ]
      ],
    ];

    // Collect all themes.
    $themes = \Drupal::moduleHandler()->invokeAll('nivo_slider_theme_info');

    // Allow theme information to be altered.
    \Drupal::moduleHandler()->alter('nivo_slider_theme_info', $themes);
    // Find the currently selected theme.
    $current_theme = $config->get('options.theme');

    // Get the current theme's settings.
    $theme = $themes[$current_theme];

    // Add the theme's resources.
    foreach (['js', 'css'] as $type) {
      if (!empty($theme['resources'][$type])) {
        foreach ($theme['resources'][$type] as $file_path) {
          $build['content']['#attached'][$type][] = $file_path;
        }
      }
    }

    return $build;
  }

}
