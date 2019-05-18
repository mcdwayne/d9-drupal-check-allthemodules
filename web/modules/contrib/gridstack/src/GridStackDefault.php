<?php

namespace Drupal\gridstack;

use Drupal\blazy\BlazyDefault;

/**
 * Defines shared plugin default settings for field formatter and Views style.
 *
 * @see FormatterBase::defaultSettings()
 * @see StylePluginBase::defineOptions()
 */
class GridStackDefault extends BlazyDefault {

  /**
   * {@inheritdoc}
   */
  public static function baseSettings() {
    return [
      'optionset' => 'default',
      'skin'      => '',
    ] + parent::baseSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function imageSettings() {
    $settings = parent::imageSettings() + self::baseSettings();

    foreach (['breakpoints', 'responsive_image_style', 'sizes'] as $key) {
      unset($settings[$key]);
    }

    return [
      'background'  => TRUE,
      'category'    => '',
      'stamp'       => '',
      'stamp_index' => 0,
    ] + $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function extendedSettings() {
    return self::imageSettings() + parent::extendedSettings();
  }

  /**
   * Returns HTML or layout related settings, none of JS to shutup notices.
   */
  public static function htmlSettings() {
    return [
      '_admin'        => FALSE,
      '_access_ipe'   => FALSE,
      'debug'         => FALSE,
      'ungridstack'   => FALSE,
      'nameshort'     => 'gs',
      'namespace'     => 'gridstack',
      'id'            => '',
      'lightbox'      => '',
      'nested'        => FALSE,
      'root'          => TRUE,
      'use_framework' => FALSE,
      'use_js'        => FALSE,
      'use_inner'     => TRUE,
      'wrapper'       => '',
    ] + self::imageSettings();
  }

  /**
   * Returns breakpoints.
   */
  public static function breakpoints() {
    return [
      'xs' => 'xsmall',
      'sm' => 'small',
      'md' => 'medium',
      'lg' => 'large',
      'xl' => 'xlarge',
    ];
  }

  /**
   * Returns theme properties.
   */
  public static function themeProperties() {
    return [
      'items',
      'optionset',
      'postscript',
      'settings',
    ];
  }

}
