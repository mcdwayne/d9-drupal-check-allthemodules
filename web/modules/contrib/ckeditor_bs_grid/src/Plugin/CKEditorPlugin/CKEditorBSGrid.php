<?php

namespace Drupal\ckeditor_bs_grid\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "bs_grid" plugin.
 *
 * @CKEditorPlugin(
 *   id = "bs_grid",
 *   label = @Translation("Bootstrap Grid")
 * )
 */
class CKEditorBSGrid extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    $path = drupal_get_path('module', 'ckeditor_bs_grid') . '/js/bs_grid';
    return [
      'bs_grid' => [
        'label' => t('Bootstrap Grid'),
        'image' => $path . '/icons/bs_grid.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'ckeditor_bs_grid') . '/js/bs_grid/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  function getDependencies(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  function getLibraries(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

}
