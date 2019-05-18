<?php

/**
 * @file
 * Hook definitions.
 */

/**
 * Hook to allow modification of a time (schedule).
 *
 * @param array $time
 *   A time which will look like:
 *   [
 *     'time' => 2019-05-06T07:30:00-06:00,
 *     'type' => [
 *        'id' => some-id,
 *        ...,
 *     ],
 *     ...
 *   ].
 * @param array $group
 *   A group which can have information (keys) about location, provider,
 *   facility, service...
 *
 * @return array
 *   An array with new items to add to the time, overriding existing items
 *   (keys) if they already exist.
 */
function hook_drupal_inquicker_alter_schedule(array $time, array $group) : array {
  return  [
    'provider' => $group['provider'],
    'location' => $group['location'],
    'facility' => $group['facility'],
    'service' => $group['service'],
  ];
}
