<?php

namespace Drupal\ckeditor_bootstrap_tabs\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "Bootstrap Tabs" plugin.
 *
 * @CKEditorPlugin(
 *   id = "bootstrapTabs",
 *   label = @Translation("Bootstrap Tabs")
 * )
 */
class BootstrapTabs extends PluginBase implements CKEditorPluginInterface, CKEditorPluginButtonsInterface {

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'bootstrapTabs' => [
        'label' => t('Bootstrap Tabs'),
        'image' => drupal_get_path('module', 'ckeditor_bootstrap_tabs') . '/js/plugins/bootstrapTabs/icons/bootstrapTabs.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return ['ckeditor_bootstrap_tabs/tabs'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'ckeditor_bootstrap_tabs') . '/js/plugins/bootstrapTabs/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

}
