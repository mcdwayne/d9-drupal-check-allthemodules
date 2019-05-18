<?php

namespace Drupal\ofed_social\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * This will create a Openfed Social Block where social network links will be
 * displayed.
 *
 * @Block(
 *   id = "ofed_social_block",
 *   admin_label = @Translation("Openfed Social Block"),
 *   category = @Translation("Openfed Social Block"),
 * )
 */
class OfedSocialBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'ofed_social_links_default',
      '#icon_links' => $this->ofed_social_get_icon_links(),
      '#attached' => [
        'library' => ['ofed_social/ofed_social_display_assets'],
      ],
    ];
  }

  /**
   * Generate a list of social icons and links, to be rendered by Ofed Social
   * Block.
   *
   * @return array of icons with links.
   */
  private function ofed_social_get_icon_links() {
    // Use current page' url and title to complete each share link information.
    $url = Url::fromRoute('<current>', [], ['absolute' => 'true']);
    $title = \Drupal::service('title_resolver')
      ->getTitle(\Drupal::request(), \Drupal::routeMatch()->getRouteObject());
    // Force title to be a string.
    $title = is_array($title) ? $title['#markup'] : $title;

    // Getting all the enabled social networks, rendering them based on the user
    // defined option.
    $networks_enabled = \Drupal::config('ofed_social.settings')
      ->get('ofed_social_networks_enabled');
    if ($networks_enabled) {
      return $this->ofed_social_render_sharelinks($url, $title, $networks_enabled);
    }
    return [];
  }

  /**
   * Return an array of social networks share links, completed with the URL and/or
   * Title of the page.
   *
   * @param Url $url
   * @param string $title
   * @param array $networks_enabled
   *
   * @return array of enabled networks with corresponding share links.
   */
  function ofed_social_render_sharelinks($url, $title, $networks_enabled) {
    $path = drupal_get_path('module', 'ofed_social');
    $networks = \Drupal::config('ofed_social.settings')
      ->get('ofed_social_networks');
    $sharelinks = [];
    // Creating 2 arrays, one with simple links and one with link and icon. Simple
    // links will be used for ShareThis theme support, icons and links will be
    // used on default theme.
    foreach ($networks_enabled as $network_key => $network) {
      $icon = [
        '#theme' => 'image',
        '#uri' => $path . '/assets/images/' . $network_key . '.svg',
        '#alt' => $networks[$network_key]['share_label'],
        '#width' => '30px',
        '#height' => '30px',
        '#attributes' => ['class' => 'ofed_social_buttons'],
      ];
      // Special case for email. We should add a target attribute.
      $target = ($network_key == 'email') ? [] : ['target' => '_blank'];
      // Special case for print. URL won't be valid so'll add a tag.
      $fragment = [];
      if ($network_key == 'print') {
        $networks[$network_key]['url'] = $url->toString(); // The current page.
        $fragment = ['fragment' => 'print'];
      }
      // Define link attributes and create a share link.
      $link_attributes = [
        'attributes' => [
          'class' => [
            'ofed_social_share_link',
            'ofed_social_share_link_' . $network_key,
          ],
        ],
      ];
      $link_attributes['attributes'] += $target;
      $share_link = Url::fromUri(str_replace('@title', $title, str_replace('@url', $url->toString(), $networks[$network_key]['url'])), $link_attributes + $fragment);

      $sharelinks[$network_key] = [
        '#type' => 'link',
        '#title' => $icon,
        '#url' => $share_link,
      ];
    }
    return $sharelinks;
  }
}
