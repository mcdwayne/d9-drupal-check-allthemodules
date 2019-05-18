<?php

/**
 * @file
 * Hooks provided by the domain_robots_txt module.
 */

/**
 * Add additional lines to the domain's robots.txt file.
 *
 * @return array
 *   An array of strings to add to the robots.txt.
 */
function hook_domain_robots_txt() {
  return [
    'Disallow: /foo',
    'Disallow: /bar',
  ];
}
