<?php

/**
 * @file
 * Page callbacks.
 */

/**
 * Page callback replacement/wrapper for og_sm_content_add_overview().
 */
function og_sm_feature_og_sm_content_add_overview($site) {
  module_load_include('inc', 'og_sm_content', 'og_sm_content.pages');
  $render_array = og_sm_content_add_overview($site);

  if (empty($render_array['#content'])) {
    $render_array = og_sm_feature_og_sm_content_add_overview_empty($site);
  }

  return $render_array;
}

/**
 * Render array if no content items available and access to manage features.
 *
 * @param object $site
 *   The Site to render the info for.
 *
 * @return array
 *   The render array.
 */
function og_sm_feature_og_sm_content_add_overview_empty($site) {
  $render_array = array(
    'info' => array(
      '#markup' => t('There are no features active providing content types.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ),
  );

  if (og_sm_site_permission_access($site, OG_SM_FEATURE_OG_PERM_ADMINISTER_FEATURES)) {
    $render_array['link'] = array(
      '#theme' => 'menu_local_action',
      '#link' => array(
        'title' => t('Administer Features'),
        'href' => 'group/node/' . $site->nid . '/admin/features',
      ),
      '#prefix' => '<ul class="action-links">',
      '#suffix' => '</ul>',
    );
  }

  return $render_array;
}
