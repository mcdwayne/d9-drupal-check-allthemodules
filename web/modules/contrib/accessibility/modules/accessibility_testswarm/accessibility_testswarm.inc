<?php

/**
 * Provides a list of filters that replaces wildcard URL arguments with test values.
 */
function _accessibility_testswarm_path_filters() {
  return array('%node' => variable_get('accessibility_testswarm_nodes', array(1, 2)),
  			   '%node_revision' => '_accessibility_testswarm_paths_node_revision',
  			   '%block' => array('bartik.tools'),
  			   '%filter_format' => array('restricted_html'),
  			   '%contact_category' => array('feedback'),
           '%taxonomy_term' => array(variable_get('accessibility_testswarm_taxonomy_term', 1)),
           '%taxonomy_vocabulary' => array(variable_get('accessibility_testswarm_taxonomy_vocabulary', 1)),
           '%comment' => array(variable_get('accessibility_testswarm_comment', 1)),
  			   );
}

/**
 * Replaces %node_revision with the vids of our installed test nodes.
 */
function _accessibility_testswarm_paths_node_revision() {
  $nids = variable_get('accessibility_testswarm_nodes', array(1, 2));
  sort($nids);
  $revision_ids = db_select('node', 'n')
  				  ->fields('n', array('vid'))
  				  ->condition('n.nid', $nids, 'IN')
  				  ->orderBy('n.nid')
  				  ->execute()
  				  ->fetchCol();
  return $revision_ids;
}

/**
 *  Replaces %block with theme/block combinations for all active themes.
 */
function _accessibility_testswarm_paths_block() {
  $block = 'tools';
  $arguments = array();
  foreach(list_themes() as $name => $theme) {
  	if($theme->status) {
  	  $arguments[] = $name .'.'. $block;
  	}
  }
  return $arguments;
}

/**
 * Replaces specific hard-coded paths with new values.
 * Paths can either be replaced with an array of values,
 * a single string, or FALSE to entirely skip this path (which
 * makes sense for certain URLs that don't return HTML, but for some
 * reason we couldn't remove by looking at their theme callback.)
 */
function _accessibility_testswarm_path_values() {
	return array(
	'admin/structure/types/manage/{bundle}/comment/fields' => 
			   array('admin/structure/types/manage/article/comment/fields',
			   		 'admin/structure/types/manage/page/comment/fields'),
	'admin/structure/types/manage/{bundle}/comment/fields/%' =>
		array('admin/structure/types/manage/article/comment/fields',
			  'admin/structure/types/manage/page/comment/fields'),
	'admin/config/people/accounts/display/default' => 'admin/config/people/accounts/display',
  'admin/structure/menu/parents' => FALSE,
  'search' => 'search/node',
  'system/timezone' => FALSE,
  'admin/config/regional/date-time/formats/lookup' => FALSE,
  'admin/compact' => FALSE,
  'admin/reports/status/run-cron' => FALSE,
  'batch' => FALSE,
  'cron' => FALSE,
  'testswarm-tests' => FALSE,
  'testswarm-browser-tests' => FALSE,
  'testswarm-run-all-tests' => FALSE,
  'testswarm-test-done' => FALSE,
  'admin/reports/status/php' => FALSE,
  'user' => FALSE,
  'user/logout' => FALSE,
  'user/login' => FALSE,
  'user/register' => FALSE,
  'admin/config/people/accounts/settings' => FALSE,
  'admin/structure/views/list' => FALSE,
  'admin/structure/views/settings/basic' => FALSE,
  'admin/reports/views-plugins' => FALSE,
  'user/login/default' => FALSE,
  'admin/people/people' => 'admin/people',
  'taxonomy/term/%taxonomy_term/feed' => FALSE,
  'admin/structure/contact/manage/%contact_category' => FALSE,
  'admin/structure/contact/manage/%contact_category/edit' => FALSE,
  'admin/structure/contact/manage/%contact_category/delete' => FALSE,
  'admin/structure/taxonomy/list' => FALSE,
  'modules/list/confirm' => FALSE,
  'admin/reports/updates' => FALSE,
  'admin/reports/updates/check' => FALSE,
  'accessibility_testswarm/summary' => FALSE
  );
}
