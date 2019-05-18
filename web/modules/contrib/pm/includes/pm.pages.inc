<?php
/**
 * @file
 * Provides various pages for pm module.
 */

/**
 * Page callback for 'pm' path.
 */
function pm_page_front() {
  $link_blocks = pm_dashboard_get_links(TRUE, 'page');
  if (!empty($link_blocks)) {
    // Divide links into two blocks.
    $count = ceil(count($link_blocks) / 2);
    $link_blocks = array_chunk($link_blocks, $count);
  }
  return array(
    'quick_links' => array(
      '#theme'  => 'pm_dashboard',
      '#links' => $link_blocks,
      '#attached' => array(
        'css' => array(
          drupal_get_path('module', 'pm') . '/pm-dashboard.css',
        ),
      ),
    ),
  );
}
