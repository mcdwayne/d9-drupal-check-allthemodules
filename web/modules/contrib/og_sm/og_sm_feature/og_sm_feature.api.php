<?php

/**
 * @file
 * API documentation about the og_sm_feature module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Inform the platform about the available feature(s).
 *
 * @return array
 *   Array containing the information about the feature(s).
 *   The Array has following content:
 *   - title : The feature title.
 *   - description : The feature description.
 *   - global configuration : An optional path to the a configuration page to
 *     set the global defaults for a feature.
 *   - site configuration : An optional path to change the configuration of the
 *     feature specific for the Site. The path should be specified without the
 *     group/node/NID/ path prefix as it will be appended automatically.
 *   - content types : An optional array of content types (machine names) that
 *     belong to the feature. The content types will be hidden and access to
 *     create them will be declined if it belongs to a feature and that feature
 *     is not enabled.
 *   - vocabularies : An optional array of vocabularies (machine names) that
 *     belong to the feature. The vocabulary will be hidden from the Site
 *     taxonomy administration pages and access to them will be declined.
 */
function hook_og_sm_feature_info() {
  $items = array();

  $items['news'] = array(
    'name' => t('News'),
    'description' => t('News content and overviews.'),
    'global configuration' => 'admin/config/group/features/news',
    'site configuration' => 'admin/features/news',
    'content types' => array('idea'),
    'vocabularies' => array('tags', 'categories'),
  );

  $items['articles'] = array(
    'name' => 'Articles',
  );

  return $items;
}

/**
 * Alter the features information as collected by hook_og_sm_feature_info().
 *
 * @param array $info
 *   The information collected by the hook_og_sm_feature_info() hook.
 */
function hook_og_sm_feature_info_alter(&$info) {
  $info['news']['site configuration'] = 'admin/feature/news-test';
  $info['news']['default status'] = FALSE;
}

/**
 * Defines an array of default values for a feature settings form.
 *
 * @param string $feature
 *   The feature name.
 * @param object $site
 *   (optional) The site node. If empty the global defaults are will be fetched.
 *
 * @return array
 *   An array of defaults.
 */
function hook_og_sm_feature_form_defaults($feature, $site = NULL) {
  return array(
    'user' => array(
      'title' => t('Users'),
    ),
  );
}

/**
 * Alters the default values collected by hook_og_sm_feature_form_defaults().
 *
 * @param array $defaults
 *   The information collected by the hook_og_sm_feature_form_defaults() hook.
 * @param string $feature
 *   The feature name.
 * @param object $site
 *   (optional) The site node. If empty the global defaults are will be fetched.
 */
function hook_og_sm_feature_form_defaults_alter(&$defaults, $feature, $site = NULL) {
  $defaults['user']['title'] = t('Site users');
}

/**
 * @} End of "addtogroup hooks".
 */
