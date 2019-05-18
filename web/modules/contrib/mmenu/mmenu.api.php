<?php

/**
 * @file
 * Hooks provided by mmenu module.
 */

/**
 * Allows modules to add more mmenus.
 */
function hook_mmenu() {
  return array(
    'mmenu_left' => array(
      'enabled' => TRUE,
      'enabled_callback' => array(
        'php' => array(
          'mmenu_enabled_callback',
        ),
        'js' => array(
          'mmenu_enabled_callback',
        ),
      ),
      'name' => 'mmenu_left',
      'title' => t('Left menu'),
      'blocks' => array(
        array(
          'title' => t('Main menu'),
          'module' => 'system',
          'delta' => 'main-menu',
          'collapsed' => FALSE,
          'wrap' => FALSE,
        ),
        array(
          'title' => t('Management'),
          'module' => 'system',
          'delta' => 'management',
          'collapsed' => FALSE,
          'wrap' => FALSE,
          'menu_parameters' => array(
            'min_depth' => 2,
          ),
        ),
      ),
      'options' => array(
        'position' => 'left',
        'classes' => 'mm-light',
      ),
      'configurations' => array(),
      // Adds your own CSS or JS handlers if you want.
      'custom' => array(
        'library' => array('examples/example.mmenu'),
      ),
    ),
  );
}

/**
 * Allows modules to alter mmenu settings.
 */
function hook_mmenu_alter(&$mmenus) {
  $mmenus['mmenu_left']['enabled'] = FALSE;
}

/**
 * Allows modules to add more mmenu themes.
 */
function hook_mmenu_theme() {
  return array(
    'mm-basic' => array(
      'name' => 'mm-basic',
      'title' => t('mm-basic'),
      'library' => array('examples/examples.mm_basic'),
    ),
  );
}

/**
 * Allows modules to alter mmenu theme settings.
 */
function hook_mmenu_theme_alter(&$classes) {
  $classes['mm-basic']['library'] = array('examples/examples.mm_basic_custom');
}

/**
 * Allows modules to add more mmenu effects.
 */
function hook_mmenu_effect() {
  return array(
    'mm-slide' => array(
      'name' => 'mm-slide',
      'title' => t('mm-slide'),
      'library' => array('examples/examples.mm_slide'),
    ),
  );
}

/**
 * Allows modules to alter mmenu effect settings.
 */
function hook_mmenu_effect_alter(&$effects) {
  $effects['mm-slide']['library'] = array('examples/examples.mm_slide_custom');
}

/**
 * Allows modules to add more mmenu icon classes.
 */
function hook_mmenu_icon() {
  $icons = array(
    'path' => array(
      'home' => 'icon-home',
      'about' => 'icon-office',
      'contact' => 'icon-envelope',
    ),
    'block' => array(
      array(
        'module' => 'system',
        'delta' => 'main-menu',
        'icon_class' => 'icon-enter',
      ),
    ),
  );
  return $icons;
}

/**
 * Allows modules to alter mmenu icon class settings.
 */
function hook_mmenu_icon_alter(&$icons) {
  $icons['path']['home'] = 'icon-home1';
}
