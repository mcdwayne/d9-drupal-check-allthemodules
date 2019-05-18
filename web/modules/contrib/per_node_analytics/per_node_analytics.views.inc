<?php

/**
 * Implements hook_views_data_alter().
 */
function per_node_analytics_views_data_alter(array &$data) {
  if (isset($data['google_analytics']['pagePath'])) {
    $data['google_analytics']['perNodeAnalyticsPagePath'] = [
      'group' => $data['google_analytics']['pagePath']['group'],
      'title' => t('Per Node Analytics Page Path'),
      'title short' => t('PNA Path'),
      'argument' => [
        'id' => 'per_node_analytics_argument',
      ],
    ];
  }
}
