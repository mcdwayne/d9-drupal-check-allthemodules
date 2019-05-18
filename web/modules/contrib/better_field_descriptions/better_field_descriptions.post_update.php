<?php

/**
 * @file
 * Post update functions for Better Field Descriptions.
 */

/**
 * Update settings config to include entity type.
 */
function better_field_descriptions_post_update_add_entity_type() {
  $config = \Drupal::configFactory();

  $bfd_config = $config->getEditable('better_field_descriptions.settings');
  $bfd_settings = $bfd_config->get('better_field_descriptions_settings');
  if (!empty($bfd_settings) && !isset($bfd_settings['node'])) {
    $new_bfds = [
      'node' => $bfd_settings,
    ];
    $bfd_settings_save = $bfd_config->set('better_field_descriptions_settings', $new_bfds);
    $bfd_settings_save->save();
  }
  $bfd_bundles = $bfd_config->get('better_field_descriptions');
  if (!empty($bfd_bundles) && !isset($bfd_bundles['node'])) {
    $new_bfdb['template'] = $bfd_bundles['template'];
    unset($bfd_bundles['template']);
    $new_bfdb['default_label'] = $bfd_bundles['default_label'];
    unset($bfd_bundles['default_label']);
    $new_bfdb['template_uri'] = $bfd_bundles['template_uri'];
    unset($bfd_bundles['template_uri']);
    $new_bfdb += [
      'node' => $bfd_bundles,
    ];
    $bfd_bundles_save = $bfd_config->set('better_field_descriptions', $new_bfdb);
    $bfd_bundles_save->save();
  }
}
