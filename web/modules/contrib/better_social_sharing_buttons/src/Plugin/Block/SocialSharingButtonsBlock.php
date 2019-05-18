<?php

namespace Drupal\better_social_sharing_buttons\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Url;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * Provides a social sharing buttons block.
 *
 * @Block(
 *   id = "better_social_sharing_buttons_block",
 *   admin_label = @Translation("Better Social Sharing Buttons block"),
 * )
 */
class SocialSharingButtonsBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $items = [];
    global $base_url;
    $request = \Drupal::request();
    if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
      $title = \Drupal::service('title_resolver')->getTitle($request, $route);
    }

    $items['page_url'] = Url::fromRoute('<current>', [], ['absolute' => TRUE]);
    $items['description'] = '';
    $items['title'] = $title;
    $items['width'] = \Drupal::state()->get('width') ?: '20px';
    $items['height'] = \Drupal::state()->get('height') ?: '20px';
    $items['radius'] = \Drupal::state()->get('radius') ?: '3px';
    $items['facebook_app_id'] = \Drupal::state()->get('facebook_app_id') ?: '';
    $items['iconset'] = \Drupal::state()->get('iconset') ?: 'social-icons--square';
    $items['services'] = \Drupal::state()->get('services') ?: [
      'facebook' => 'facebook',
      'twitter' => 'twitter',
      'linkedin' => 'linkedin',
      'googleplus' => 'googleplus',
      'email' => 'email',
    ];
    $items['base_url'] = $base_url;
    return [
      '#theme' => 'better_social_sharing_buttons',
      '#items' => $items,
    ];
  }

}
