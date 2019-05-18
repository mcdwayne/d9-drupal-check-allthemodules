<?php

/**
 * @file
 * Breakgen API file describing the API hooks.
 */

/**
 * Hook for altering the image style before breakgen saves it.
 *
 * @param \Drupal\image\Entity\ImageStyle $imageStyle
 *   Image style to alter.
 */
function hook_breakgen_image_style_alter(
  \Drupal\image\Entity\ImageStyle &$imageStyle
) {
  // E.G: change properties within the image style.
}

/**
 * Hook for altering a image style effect before breakgen adds.
 *
 * @param array $effectConfiguration
 *   Array of configuration values of image styles.
 */
function hook_breakgen_image_style_effect_alter(array &$effectConfiguration) {
  // E.G: modify effect before it gets added.
}

/**
 * Hook that fires before breakgen clears all image styles related to breakgen.
 */
function breakgen_pre_clear_image_styles() {
  // E.G: clear any entities related to breakgen image styles.
}

/**
 * Hook that fires before breakgen clears all image styles related to breakgen.
 *
 * @param mixed $key
 *   Breakgen configuration key.
 * @param \Drupal\breakpoint\BreakpointInterface $breakpoint
 *   Breakpoint Interface of plugin.
 * @param array $breakgen
 *   Breakgen configuration from breakgen theme file.
 */
function hook_breakgen_post_save_image_styles(
  $key,
  Drupal\breakpoint\BreakpointInterface &$breakpoint,
  array &$breakgen
) {
  // E.G: create an entity that depends on the image style.
}