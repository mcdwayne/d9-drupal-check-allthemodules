<?php

/**
 * @file
 * Segment.io Drupal module API.
 */

/**
 * Retrieves hook information available to Segment.io.
 */
function hook_segmentio_info() {
  return [
    'example:segmentio_mytrackinginfo' => t("MyModule's MyTrackingInfo"),
  ];
}

/**
 * Segment.io callback; Set attributes for analytics.js calls.
 */
function example_segmentio_mytrackinginfo(&$variables = []) {
  // Identify the user.
  $variables['identify']['userId'] = 1;
  $variables['identify']['traits']['name'] = 'admin';
  $variables['identify']['traits']['email'] = 'example@example.com';

  // Identify the page.
  $variables['page']['category'] = 'basic page';
  $variables['page']['name'] = 'Cookie policy';
  $variables['page']['properties']['nid'] = 1;
  $variables['page']['properties']['uid'] = 1;

  // Alias associated identified users with group.
  $variables['group']['groupId'] = 'Group Name';
  $variables['group']['traits']['address1'] = '1 Example Lane';

  // Alias two identities.
  $variables['alias']['userId'] = 2;
  $variables['alias']['previousId'] = 1;

  // Track an event.
  $variables['track'][] = [
    'event' => 'Downloaded a PDF',
    'properties' => [
      'pdf_name' => 'examplepdf.pdf',
    ],
  ];
}
