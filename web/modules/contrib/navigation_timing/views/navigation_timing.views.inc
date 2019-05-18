<?php

/**
 * @file
 * Views hooks implemented for the navigation_timing module.
 */

/**
 * Implements hook_views_data().
 */
function navigation_timing_views_data() {
  $data['navigation_timing_data']['table'] = array(
    'group' => t('Navigation Timing'),
    'base' => array(
      'field' => 'tid',
      'title' => t('Navigation Timing'),
      'help' => t('Navigation Timing data.'),
    ),
  );

  $data['navigation_timing_data']['tid'] = array(
    'title' => t('tid'),
    'help' => t('tid of navigation timing data.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'title' => t('tid'),
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
  );

  $data['navigation_timing_data']['gid'] = array(
    'title' => t('Group'),
    'help' => t('The navigation_timing_group of this record.'),
    'relationship' => array(
      'base' => 'navigation_timing_group',
      'field' => 'gid',
      'handler' => 'views_handler_relationship',
      'label' => t('Navigation Timing Group'),
    ),
  );

  $data['navigation_timing_data']['uid'] = array(
    'title' => t('User'),
    'help' => t('The user who sent the navigation_timing data.'),
    'relationship' => array(
      'base' => 'users',
      'field' => 'uid',
      'handler' => 'views_handler_relationship',
      'label' => t('Navigation Timing User'),
    ),
  );

  // 'navigationStart' is a milliseconds timestamp.
  $data['navigation_timing_data']['navigationStart'] = array(
    'title' => t('navigationStart'),
    'help' => t('Time when navigation timing was recorded.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'title' => t('Submitted'),
      'handler' => 'views_handler_field_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
  );

  // User Agent.
  $data['navigation_timing_data']['ua'] = array(
    'title' => t('User Agent'),
    'help' => t('User Agent of this record.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'title' => t('User Agent'),
      'handler' => 'views_handler_filter_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // Drupal path.
  $data['navigation_timing_data']['path'] = array(
    'title' => t('Path'),
    'help' => t('Path of the navigation timing.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'title' => t('Path'),
      'handler' => 'views_handler_filter_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // Current theme.
  $data['navigation_timing_data']['theme'] = array(
    'title' => t('Theme'),
    'help' => t('Theme of the navigation timing.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'title' => t('Theme'),
      'handler' => 'views_handler_filter_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // List of all the numeric data saved.
  $timings = array(
    'redirectStart',
    'unloadEventStart',
    'unloadEventEnd',
    'redirectEnd',
    'fetchStart',
    'domainLookupStart',
    'domainLookupEnd',
    'connectStart',
    'secureConnectionStart',
    'connectEnd',
    'requestStart',
    'responseStart',
    'responseEnd',
    'domLoading',
    'domInteractive',
    'domContentLoadedEventStart',
    'domContentLoadedEventEnd',
    'domComplete',
    'loadEventStart',
    'loadEventEnd',
    'firstPaint',
    'clientWidth',
    'clientHeight',
  );

  foreach ($timings as $timing) {
    $data['navigation_timing_data'][$timing] = array(
      'title' => $timing,
      'help' => $timing,
      'field' => array(
        'handler' => 'views_handler_field_numeric',
        'click sortable' => TRUE,
      ),
      'filter' => array(
        'title' => $timing,
        'handler' => 'views_handler_filter_numeric',
      ),
      'sort' => array(
        'handler' => 'views_handler_sort',
      ),
      'argument' => array(
        'handler' => 'views_handler_argument_numeric',
      ),
    );
  }

  return $data;
}
