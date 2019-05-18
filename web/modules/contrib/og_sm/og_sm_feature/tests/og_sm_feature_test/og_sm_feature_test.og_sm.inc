<?php

/**
 * @file
 * Hooks implemented to test the Variable defaults functionality.
 */

/**
 * Implements hook_og_sm_feature_info().
 */
function og_sm_feature_test_og_sm_feature_info() {
  $items = array();

  $items['news'] = array(
    'name' => t('News'),
    'description' => t('News content and overviews.'),
    'content types' => array('news'),
    'global configuration' => 'admin/config/group/features/news',
    'site configuration' => 'admin/features/news',
  );
  $items['articles'] = array(
    'name' => t('Articles'),
    'vocabularies' => array('categories', 'tags'),
  );
  $items['news and blogs'] = array(
    'name' => t('News and Blogs'),
    'content types' => array('news', 'blog_post'),
    'vocabularies' => array('tags'),
  );

  return $items;
}

/**
 * Implements hook_og_sm_feature_info_alter().
 */
function og_sm_feature_test_og_sm_feature_info_alter(&$info) {
  $info['news']['global configuration'] = 'admin/config/group/features/news-test';
  $info['news']['site configuration'] = 'admin/features/news-test';
}

/**
 * Implements hook_og_sm_feature_form_defaults().
 */
function og_sm_feature_test_og_sm_feature_form_defaults($feature, $site) {
  if ($feature !== 'news') {
    return;
  }

  return array(
    $feature => array(
      'title' => t('News'),
      'overview' => TRUE,
    ),
  );
}

/**
 * Implements hook_og_sm_feature_form_defaults_alter().
 */
function og_sm_feature_test_og_sm_feature_form_defaults_alter(&$defaults, $feature, $site) {
  if ($feature !== 'news') {
    return;
  }

  $defaults[$feature]['title'] = t('News test');
}
