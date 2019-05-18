<?php

/**
 * @file
 * Drupal 8 module hooks/alters for the Drupal+ module.
 */

use Drupal\plus\Plus;
//
///**
// * {@inheritdoc}
// *
// * @see \Drupal\plus\Plugin\Alter\ElementInfo::alter()
// * @see \Drupal\plus\Plugin\Theme\ThemeInterface::doAlter()
// */
//function plus_element_info_alter(&$data, &$context1 = NULL, &$context2 = NULL) {
//  Plus::getActiveTheme()->doAlter(__FUNCTION__, $data, $context1, $context2);
//}
//
///**
// * {@inheritdoc}
// *
// * @see \Drupal\plus\Plugin\Theme\ThemeInterface::doAlter()
// */
//function plus_form_alter(&$data, &$context1 = NULL, &$context2 = NULL) {
//  Plus::getActiveTheme()->doAlter(__FUNCTION__, $data, $context1, $context2);
//}
//
///**
// * {@inheritdoc}
// *
// * @see \Drupal\plus\Core\Form\SystemThemeSettings::alterForm()
// * @see \Drupal\plus\Plugin\Theme\ThemeInterface::doAlter()
// */
//function plus_form_system_theme_settings_alter(&$data, &$context1 = NULL, &$context2 = NULL) {
//  Plus::getActiveTheme()->doAlter(__FUNCTION__, $data, $context1, $context2);
//}
//
///**
// * {@inheritdoc}
// *
// * @see \Drupal\plus\Plugin\Theme\ThemeInterface::doAlter()
// */
//function plus_js_settings_alter(&$data, &$context1 = NULL, &$context2 = NULL) {
//  Plus::getActiveTheme()->doAlter(__FUNCTION__, $data, $context1, $context2);
//}
//
///**
// * {@inheritdoc}
// *
// * @see \Drupal\plus\Plugin\Alter\LibraryInfo::alter()
// * @see \Drupal\plus\Plugin\Theme\ThemeInterface::doAlter()
// */
//function plus_library_info_alter(&$data, &$context1 = NULL, &$context2 = NULL) {
//  Plus::getActiveTheme()->doAlter(__FUNCTION__, $data, $context1, $context2);
//}
//
///**
// * {@inheritdoc}
// */
//function plus_module_implements_alter(&$implementations, $hook) {
//  // Move all implementations of the plus module alters to the end of the
//  // list. This effectively allows these alters to reside in between modules
//  // and themes, thus essentially making this module act like a "base theme".
//  if (isset($implementations['plus'])) {
//    $group = $implementations['plus'];
//    unset($implementations['plus']);
//    $implementations['plus'] = $group;
//  }
//}
//
///**
// * {@inheritdoc}
// *
// * @see \Drupal\plus\Plugin\Alter\PageAttachments::alter()
// * @see \Drupal\plus\Plugin\Theme\ThemeInterface::doAlter()
// */
//function plus_page_attachments_alter(&$data, &$context1 = NULL, &$context2 = NULL) {
//  Plus::getActiveTheme()->doAlter(__FUNCTION__, $data, $context1, $context2);
//}
//
///**
// * {@inheritdoc}
// *
// * @see \Drupal\plus\Plus::preprocess()
// * @see \Drupal\plus\Plugin\Theme\ThemeInterface::doPreprocess()
// */
//function plus_preprocess(&$variables, $hook, $info) {
//  Plus::getActiveTheme()->doPreprocess($variables, $hook, $info);
//}
//
///**
// * {@inheritdoc}
// *
// * @see \Drupal\plus\Plugin\Theme\ThemeInterface::getThemeHooks()
// */
//function plus_theme($existing, $type, $theme, $path) {
//  return Plus::getActiveTheme()->getThemeHooks($existing, $type, $theme, $path);
//}
//
///**
// * {@inheritdoc}
// *
// * @see \Drupal\plus\Plugin\Alter\ThemeRegistry::alter()
// * @see \Drupal\plus\Plugin\Theme\ThemeInterface::doAlter()
// *
// * @todo Remove if a proper replacement for the theme.registry service can be
// * implemented.
// */
//function plus_theme_registry_alter(&$data, &$context1 = NULL, &$context2 = NULL) {
//  Plus::getActiveTheme()->doAlter(__FUNCTION__, $data, $context1, $context2);
//}
//
///**
// * {@inheritdoc}
// *
// * @see \Drupal\plus\Plugin\Alter\ThemeSuggestions::alter()
// * @see \Drupal\plus\Plugin\Theme\ThemeInterface::doAlter()
// */
//function plus_theme_suggestions_alter(&$data, &$context1 = NULL, &$context2 = NULL) {
//  Plus::getActiveTheme()->doAlter(__FUNCTION__, $data, $context1, $context2);
//}
//
///**
// * Implements hook_themes_installed().
// *
// * {@inheritdoc}
// */
//function plus_themes_installed($theme_list) {
//  foreach (Plus::getThemes($theme_list) as $theme) {
//    $theme->install();
//  }
//}
//
///**
// * Implements hook_themes_uninstalled().
// *
// * {@inheritdoc}
// */
//function plus_themes_uninstalled(array $themes) {
//  foreach (Plus::getThemes($themes) as $theme) {
//    $theme->uninstall();
//  }
//}
