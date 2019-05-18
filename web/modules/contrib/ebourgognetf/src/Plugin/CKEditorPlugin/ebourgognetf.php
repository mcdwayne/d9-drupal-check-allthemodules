<?php

namespace Drupal\ebourgognetf\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "ebourgognetf" plugin.
 *
 * @CKEditorPlugin(
 *   id = "ebourgognetf",
 *   label = @Translation("Teleformulaires e-bourgogne"),
 * )
 */
class EbourgogneTf extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'ebourgognetf') . '/js/plugins/ebourgognetf/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return array(
      'core/drupal.ajax',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array(
      'ebourgogneTf_AddForm' => t("Add an e-bourgogne EForm"),
      'ebourgogneTf_Form' => t("e-bourgogne EForm"),
      'ebourgogneTf_LinkText' => t("Link text"),
      'ebourgogneTf_NewTab' => t("Open in new tab"),
      'ebourgogneTf_EmptyLink' => t("Your link text cannot be empty"),
      'EbourgogneTf_Ok' => t("OK"),
      'EbourgogneTf_Cancel' => t("Cancel"),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    $path = drupal_get_path('module', 'ebourgognetf') . '/js/plugins/ebourgognetf';
    return array(
      'EbourgogneTf' => array(
        'label' => t("Insert e-bourgogne EForms"),
        'image' => $path . '/logo.png',
      ),
    );
  }

}
