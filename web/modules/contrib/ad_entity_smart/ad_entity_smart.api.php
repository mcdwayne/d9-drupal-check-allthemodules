<?php

/**
 * @file
 * Documentation for the Advertising Entity: Smart AdServer module.
 *
 * This file contains no working PHP code; it exists to provide additional
 * documentation for doxygen as well as to document hooks in the standard
 * Drupal manner.
 */

use Drupal\ad_entity\TargetingCollection;

/**
 * Alter the ad target before render.
 *
 * @param \Drupal\ad_entity\TargetingCollection $targeting_collection
 *   GTM data layer data for the current page.
 */
function hook_ad_entity_smart_targeting_alter(TargetingCollection &$targeting_collection) {

}
