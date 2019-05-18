<?php

namespace Drupal\reactify_utilities\Plugin\rest\Resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Class SettingsResource provides rest endpoint with theme settings.
 *
 * @RestResource (
 *   id = "theme_settings_resource",
 *   label = @Translation("Theme settings"),
 *   uri_paths = {
 *     "canonical" = "/api/theme_settings"
 *   }
 * )
 */
class SettingsResource extends ResourceBase {

  /**
   * {@inheritdoc}
   */
  public function get() {
    global $base_url;
    $config = \Drupal::config('system.site');
    $site_name = $config->get('name');
    $settings['site_name'] = $site_name;
    $settings['base_url'] = $base_url;
    $settings['logo_url'] = theme_get_setting('logo', 'reactify')['url'];
    $settings['full_width'] = theme_get_setting('full_width', 'reactify');
    $settings['pages'] = [];

    // Check whether there are specified page ids.
    $front_page_id = theme_get_setting('front_page', 'reactify');
    $about_page_id = theme_get_setting('about_page', 'reactify');
    if (!empty($front_page_id)) {
      $front_page = [
        'name' => 'front',
        'id' => $front_page_id,
      ];
      array_push($settings['pages'], $front_page);
    }

    if (!empty($about_page_id)) {
      $about_page = [
        'name' => 'about',
        'id' => $about_page_id,
      ];
      array_push($settings['pages'], $about_page);
    }

    // Check banner settings.
    $banner_block_id = theme_get_setting('banner_block', 'reactify');

    if (!empty($banner_block_id)) {
      $settings['banner_block_id'] = $banner_block_id;
    }

    // Layout settings.
    $settings['layout']['showHeader'] = theme_get_setting('layout.show_header', 'reactify');
    $settings['layout']['showFooter'] = theme_get_setting('layout.show_footer', 'reactify');
    $settings['layout']['showBanner'] = theme_get_setting('layout.show_banner', 'reactify');
    $settings['layout']['showSidebar'] = theme_get_setting('layout.show_sidebar', 'reactify');
    $settings['layout']['sidebarPosition'] = theme_get_setting('layout.sidebar_position', 'reactify');

    // Theme text settings.
    $settings['text']['footerText'] = theme_get_setting('theme_text.footer_text', 'reactify');

    // Dashboard statistics settings.
    $settings['dashboard']['dashboardStats']['enabled'] = theme_get_setting('dashboard.show_dashboard_stats', 'reactify');

    return new ResourceResponse($settings);
  }

  /**
   * {@inheritdoc}
   */
  public function post($data) {
    return new ResourceResponse($data);
  }

}
