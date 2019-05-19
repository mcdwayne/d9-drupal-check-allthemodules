<?php

/**
 * @file
 * Views plugin settings in this file.
 */

/**
 * Implements hook_views_data().
 */
function social_stats_views_data() {
  $data = array();

  // Create a new group by which the of this module plugins would be grouped.
  $data['social_stats_facebook']['table']['group'] = t('Social Stats');

  // Join the table with default node table.
  $data['social_stats_facebook']['table']['join'] = array(
    'node_field_data' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );

  // Define field, filter and sort type plugins for Facebook likes.
  $data['social_stats_facebook']['likes'] = array(
    'title' => t('Facebook likes'),
    'help' => t('Number of Facebook likes for this node.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // Define field, filter and sort type plugins for Facebook shares.
  $data['social_stats_facebook']['shares'] = array(
    'title' => t('Facebook shares'),
    'help' => t('Number of Facebook shares for this node.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // Define field, filter and sort type plugins for Facebook comments.
  $data['social_stats_facebook']['comments'] = array(
    'title' => t('Facebook comments'),
    'help' => t('Number of Facebook comments on this node.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // Define field, filter and sort type plugins for Facebook total.
  $data['social_stats_facebook']['total'] = array(
    'title' => t('Facebook total'),
    'help' => t('Value of (Facebook likes + Facebook shares + Facebook comments).'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // Use the same group which was defined earlier to group plugins.
  $data['social_stats_linkedin']['table']['group'] = t('Social Stats');

  // Join the table with default node table.
  $data['social_stats_linkedin']['table']['join'] = array(
    'node_field_data' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );

  // Define field, filter and sort type plugins for LinkedIn shares.
  $data['social_stats_linkedin']['shares'] = array(
    'title' => t('LinkedIn shares'),
    'help' => t('Value of times this node has been shared on LinkedIn.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // Use the same group which was defined earlier to group plugins.
  $data['social_stats_gplus']['table']['group'] = t('Social Stats');

  // Join the table with default node table.
  $data['social_stats_gplus']['table']['join'] = array(
    'node_field_data' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );

  // Define field, filter and sort type plugins for plusone total count.
  $data['social_stats_gplus']['total'] = array(
    'title' => t('Google+ Total Count'),
    'help' => t('Number of times this node has been +1ed and shared.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // Define field, filter and sort type plugins for googleplus plusone.
  $data['social_stats_gplus']['plusone'] = array(
    'title' => t('Google+ plusone'),
    'help' => t('Number of times this node has been +1ed.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // Define field, filter and sort type plugins for googleplus share count.
  $data['social_stats_gplus']['shares'] = array(
    'title' => t('Google+ Share Count'),
    'help' => t('Number of times this node has been shared on Google+.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // Use the same group which was defined earlier to group plugins.
  $data['social_stats_total']['table']['group'] = t('Social Stats');

  // Join the table with default node table.
  $data['social_stats_total']['table']['join'] = array(
    'node_field_data' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );

  // Define field, filter and sort type plugins for total shares.
  $data['social_stats_total']['total'] = array(
    'title' => t('Total social shares'),
    'help' => t('(Facebook total + LinkedIn Shares + Google plus shares)'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  return $data;
}
