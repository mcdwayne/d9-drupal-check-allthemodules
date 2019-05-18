<?php

/**
 * @file
 * Contains Drush hooks.
 */

/**
 * Implements hook_drush_command().
 */
function micon_drush_command() {
  $items = array();

  $items['micon'] = array(
    'description' => 'Generate SCSS mixins and variables for currently enabled Micon icon sets.',
    'arguments' => array(
      'path'         => 'The destination where the _micon.scss mixin file should be created. Do not include a trailing slash.',
    ),
    'examples' => array(
      'drush micon "themes/my_theme/src/scss/base"' => 'Creates the SCSS mixin file and places it within SITE_ROOT/themes/my_theme/src/scss/base',
    ),
  );

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function micon_drush_help($section) {
  switch ($section) {
    case 'drush:micon':
      return dt('This command will generate SCSS mixins and variables for currently enabled Micon icon sets.');
  }
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_micon($path = NULL) {

  // If no $name provided, abort.
  if (!$path) {
    drush_print(dt('Location path missing. See help using drush micon --help.'));
    return;
  }

  $path = drush_get_context('DRUSH_DRUPAL_ROOT') . '/' . $path;
  if (!file_exists($path)) {
    drush_print(dt('Location directory not found. See help using drush micon --help.'));
    return;
  }

  $fullpath = $path . '/_micon.scss';
  $miconIconManager = \Drupal::service('micon.icon.manager');

  $content = [];
  $content[] = '/**';
  $content[] = '* Micon icon mixins and variables.';
  $content[] = '*';
  $content[] = '* DO NOT MAKE MANUAL CHANGES TO THIS FILE';
  $content[] = '* Generate via `drush micon ' . $path . '`.';
  $content[] = '*/' . "\n";
  $content[] = '@mixin micon($package: fa, $icon: rebel, $position: before) {';
  $content[] = '  @if $position == both {';
  $content[] = '    $position: \'before, &:after\';';
  $content[] = '  }' . "\n";
  $content[] = '  &:#{$position} {';
  $content[] = '    font-family: \'#{$package}\' !important;';
  $content[] = '    display: inline-block;';
  $content[] = '    speak: none;';
  $content[] = '    font-style: normal;';
  $content[] = '    font-weight: normal;';
  $content[] = '    font-variant: normal;';
  $content[] = '    text-transform: none;';
  $content[] = '    line-height: 1;';
  $content[] = '    vertical-align: middle;';
  $content[] = '    -webkit-font-smoothing: antialiased; // sass-lint:disable-line no-vendor-prefixes';
  $content[] = '    -moz-osx-font-smoothing: grayscale; // sass-lint:disable-line no-vendor-prefixes';
  $content[] = '    content: "#{map-get($micons, #{$package}-#{$icon})}"; // sass-lint:disable-line quotes';
  $content[] = '    @content;';
  $content[] = '  }';
  $content[] = '}' . "\n";

  $content[] = '$micons: (';
  foreach ($miconIconManager->getIcons() as $package_id => $icons) {
    foreach ($icons as $icon) {
      $content[] = '  ' . $icon->getSelector() . ': \'' . $icon->getHex() . '\',';
    }
  }
  $content[] = ');';

  $content[] = "\n";

  file_put_contents($fullpath, implode("\n", $content));

  // Notify user.
  $message = 'Successfully created the Micon SCSS file in: !path';

  $message = dt($message . '.', array(
    '!path' => $path,
  ));
  drush_print($message);

}
