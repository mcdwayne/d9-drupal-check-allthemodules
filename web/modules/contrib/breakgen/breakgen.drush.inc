<?php

/**
 * @file
 * Drush commands file of breakgen module.
 */

/**
 * Implements hook_drush_command().
 */
function breakgen_drush_command() {
  return [
    'breakpoints-generate' => [
      'description' => 'This command generates breakgen breakpoints for Drupal 8 out of the theme file.',
      'aliases'     => ['bg'],
      'arguments'   => [
        'theme' => 'An enabled theme to use for the breakpoint generation',
      ],
    ],
    'breakpoints-clear'    => [
      'description' => 'This command clears breakgen breakpoints for Drupal 8 out of the theme file.',
      'aliases'     => ['bc'],
      'arguments'   => [
        'theme' => 'An enabled theme to use for the breakpoint generation',
      ],
    ],
  ];
}

/**
 * Do generate for breakgen module.
 *
 * @param string|null $theme
 *   The theme to use for the breakpoint file.
 */
function drush_breakgen_breakpoints_generate($theme = NULL) {
  $generator = \Drupal::service('breakgen.service.generator');
  $generator->generate($theme);
}

/**
 * Do clear for breakgen module.
 */
function drush_breakgen_breakpoints_clear() {
  $generator = \Drupal::service('breakgen.service.generator');
  $generator->clear();
}
