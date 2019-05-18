<?php

namespace Drupal\aping\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'aping' Social Wall block.
 *
 * @Block(
 *  id = "social_wall",
 *  admin_label = @Translation("Social Wall"),
 * )
 */
class SocialWall extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#theme'] = 'social_wall';
    // $build['#variables']['name'] = 'Social Wall';
    $build['#attached']['library'][] = 'aping/aping.angularjs';
    $build['#attached']['library'][] = 'aping/aping.aping';
    $build['#attached']['library'][] = 'aping/aping.dependancies';
    $build['#attached']['library'][] = 'aping/aping.socialwall';
    $build['#attached']['library'][] = 'aping/aping.design';
    $build['#attached']['library'][] = 'aping/apingConfig';
    $build['#attached']['drupalSettings']['aping']['apingConfig']['path'] = ('/' . (drupal_get_path('module', 'aping')));
    return $build;
  }
}
