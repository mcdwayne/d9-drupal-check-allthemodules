<?php

use \Drupal\Core\Config\FileStorage;

/**
 * Installs the file upload element
 */
function champs_blog_update_8002() {

  $configs_to_install = [ // This array is probably not needed - the
    'paragraphs.paragraphs_type.cbsf_file_download',
    'core.entity_form_display.paragraph.cbsf_file_download.default',
    'core.entity_view_display.paragraph.cbsf_file_download.default',
    'field.storage.paragraph.field_cb_file',
    'field.field.paragraph.cbsf_file_download.field_cb_file',
    'field.field.paragraph.cbsf_file_download.field_cb_heading',
    'field.field.paragraph.cbsf_file_download.field_cb_icon',
    'field.field.paragraph.cbsf_file_download.field_cb_text',
  ];

  // NOTE:  this should be tested!
  /**
   * This function automatically looks in the appropriate update folder and
   * checks for configuration.yml files that have the correct prefix, then
   * installs them in the right place (OR updates them, if an update is what is
   * needed).
   */
  _update_or_install_config('paragraphs.paragraphs_type', '8002', 'champs_blog');
  _update_or_install_config('field.storage.paragraph', '8002', 'champs_blog');
  _update_or_install_config('field.field.paragraph', '8002', 'champs_blog');
  _update_or_install_config('core.entity_view_display.paragraph', '8002', 'champs_blog');
  _update_or_install_config('core.entity_form_display.paragraph', '8002', 'champs_blog');



  /* *****************************************************************************
   Editing the cb_element paragraph type to include cb_file_download
  ***************************************************************************** */

  $config_factory = \Drupal::configFactory();
  $config = $config_factory->getEditable('field.field.paragraph.cb_element.field_cb_element');

  // Add the dependency on the paragraph type
  $config_dependencies = $config->get('dependencies.config');
  $updated_dependencies = array_push($config_dependencies, 'paragraphs.paragraphs_type.cbsf_file_download');
  $config->set('dependencies.config', $updated_dependencies);

  // Add the File Download paragraph as an available paragraph to the cb_element

  // NOTE:  the easier way to achieve this might be to have the updated .yml file
  // and use the _update_or_install_config function to update it to the newer
  // version.
  $config_dependencies = $config->get('settings.handler_settings.target_bundles');
  $config_to_add = array('cbsf_file_download' => 'cbsf_file_download');
  $updated_dependencies = array_unique(array_merge($config_to_add, $config_dependencies));
  $config->set('settings.handler_settings.target_bundles', $updated_dependencies);

  $config->save();
}

/**
 * Updates the tags and topics view to utilise the row template instead of
 * the rewriting field.
 */
function champs_blog_update_8003() {
  _update_or_install_config('views.view', '8003', 'champs_blog');
}


/**
 * Adds the metatag configuration to both of the taxonomy vocabularies that
 * are used in the champions blog setup.
 */
function champs_blog_update_8004() {
  _update_or_install_config('field.storage', '8004', 'champs_blog');
  _update_or_install_config('field.field', '8004', 'champs_blog');
  _update_or_install_config('core.entity_form_display.taxonomy_term', '8004', 'champs_blog');
}

/**
 * Attempt to update ALL config from the install folder.
 * NOTE: this seems to work - we need to write a CSS framework for the blog that
 * would allow it to be easily recompiled and edited.
 */
// function champs_blog_update_8005() {
//   $updated = _reinstall_module_config('champs_blog');
// }