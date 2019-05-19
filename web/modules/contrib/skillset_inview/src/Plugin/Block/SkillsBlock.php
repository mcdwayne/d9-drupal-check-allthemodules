<?php

namespace Drupal\skillset_inview\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;

/**
 * Class SkillsBlock.
 *
 * @package Drupal\skillset_inview\Form
 *
 * @Block(
 *   id = "skillset_inview_zero",
 *   admin_label = @Translation("Skillset Inview"),
 *   category = @Translation("Custom Blocks"),
 * )
 */
class SkillsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [];
    $block['#cache'] = [
      'contexts' => ['user.permissions'],
    ];
    $db = \Drupal::service('database');
    $results = $db->select('skillset_inview', 'w')
      ->fields('w')
      ->orderBy('weight')
      ->execute()
      ->fetchAll();
    if (!empty($results)) {
      $configHeading = \Drupal::config('skillset_inview.heading');
      $heading = $configHeading->get('heading');
      $configColor = \Drupal::config('skillset_inview.color');
      $color_active = '0';
      $color = [];
      if (!empty($configColor)) {
        $color_active = $configColor->get('color-active');
        if ($color_active == 1) {
          $color = [
            'bar' => $configColor->get('color-bar'),
            'bar_opacity' => $configColor->get('color-bar-opacity'),
            'back' => $configColor->get('color-back'),
            'back_opacity' => $configColor->get('color-back-opacity'),
            'border' => $configColor->get('color-border'),
            'labels' => $configColor->get('color-labels'),
            'percent_inside' => $configColor->get('color-percent-inside'),
            'percent_outside' => $configColor->get('color-percent-outside'),
          ];
        }
      }
      $items = [];
      foreach ($results as $item) {
        $items[] = (array) $item;
      }
      $block['content'] = [
        '#theme' => 'skillset_inview',
        '#header' => $heading,
        '#items' => $items,
        '#color_active' => $color_active,
        '#color' => $color,
        '#cache' => [
          'keys' => ['skillset-inview'],
//           'tags' => [
//             'colorize' => $color_active,
//             'color' => $color,
//             'header' => $heading,
//             'items' => $items,
//           ],
        ],
      ];
      $block['assets'] = [
        '#attached' => [
          'library' => [
            'skillset_inview/block',
          ],
        ],
      ];
    }
    else {
      if (\Drupal::currentUser()->hasPermission('administer skillset inview')) {
        $add_skill = Link::createFromRoute(t('Add Skill'), 'skillset_inview.add_form');
        $block['#markup'] = t('No skills have been added yet!  @url', ['@url' => $add_skill->toString()]);
      }
    }

    return $block;
  }

}
