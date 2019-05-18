<?php

/**
 * @file
 * Hooks related to the loading bar module.
 */

/**
 * Provide default prebuilt styles of progress bar to choose.
 *
 * @return array
 *   An associative array of available prebuilt styles.
 */
function hook_loading_bar_preset() {
  return [
    'loading' => [
      'preset' => 'text',
      'type' => 'fill',
      'img' => 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="70" height="20" viewBox="0 0 70 20"><text x="35" y="10" text-anchor="middle" dominant-baseline="central" font-family="arial">LOADING</text></svg>',
      'fill-background-extrude' => 1.3,
      'pattern-size' => 100,
      'fill-dir' => "ltr",
      'img-size' => "70,20",
      'bbox' => '0 0 70 20',
    ],
  ];
}
