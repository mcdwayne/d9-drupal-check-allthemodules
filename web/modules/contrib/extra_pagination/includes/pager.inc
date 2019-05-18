<?php

/**
 * @author Adrian Marin (https://drupal.org/u/amarincolas)
 *
 * @file
 * Functions to aid in presenting database results as a set of pages.
 */

/**
 * Return a new set of pager items between the last visible pager link and the
 * maximum pager number.
 *
 * @param int $pager_last
 *  The last visible pager number.
 * @param int $pager_max
 *  The maximum pager number.
 * @param array $route_parameters
 *  An associative array of the route parameters.
 * @param array $parameters
 *  An associative array of query string parameters to append to the pager
 *  links.
 * @param int $element
 *  An optional integer to distinguish between multiple pagers on one page.
 * @return array
 *  A set of pager items between the last visible pager link and the
 *  maximum pager number.
 */
function pager_extra_pages($pager_last, $pager_max, $route_parameters, $parameters, $element) {
  $items = [];
  $base = 10;
  $count = 0;

  $i = floor($pager_last / $base) * $base;
  while(($i + $base) < $pager_max) {

    $i += $base;
    if (abs($i-$pager_last) > 1) {

      $options = [
        'query' => pager_query_add_page($parameters, $element, $i),
      ];
      $items[$i]['href'] = \Drupal::url('<current>', $route_parameters, $options);
    }
    $count++;

    // This is done to make bigger intervals.
    if ($count === 10) {
      $base = $base * $base;
    }
  }

  return $items;
}
