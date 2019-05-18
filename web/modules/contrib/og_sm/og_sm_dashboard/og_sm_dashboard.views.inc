<?php

/**
 * @file
 * Views plugins & hooks.
 */

/**
 * Implements hook_views_default_views_alter().
 */
function og_sm_dashboard_views_default_views_alter(&$views) {
  if (isset($views['og_sm_content_platform_admin_overview'])) {
    $views['og_sm_content_platform_admin_overview']->display['overview']->display_options['path'] = 'admin/dashboard/content';
    $views['og_sm_content_platform_admin_overview']->display['overview']->display_options['menu']['title'] = 'Content';
    $views['og_sm_content_platform_admin_overview']->display['overview']->display_options['menu']['weight'] = 5;
    $translatables['og_sm_content_platform_admin_overview'] = array(
      t('Content'),
    );
  }
  if (isset($views['og_sm_comment_platform_admin_overview'])) {
    $views['og_sm_comment_platform_admin_overview']->display['overview']->display_options['path'] = 'admin/dashboard/comments';
    $views['og_sm_comment_platform_admin_overview']->display['overview']->display_options['menu']['title'] = 'Comments';
    $views['og_sm_comment_platform_admin_overview']->display['overview']->display_options['menu']['weight'] = 10;
    $translatables['og_sm_comment_platform_admin_overview'] = array(
      t('Comments'),
    );
  }
}
