<?php

/**
 * @file
 * Contains \Drupal\elfinder\Plugin\CKEditorPlugin\elFinder.
 */

namespace Drupal\elfinder\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginBase;

/**
 * Defines elFinder plugin for CKEditor.
 *
 * @CKEditorPlugin(
 *   id = "elfinder",
 *   label = "elFinder"
 * )
 */
class elFinder extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'elfinder') . '/editors/ckeditor/ckeditor.callback.js';
  }


  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array(
      'filebrowserBrowseUrl' => \Drupal::url('elfinder'),
      'elFinderImageIcon' => drupal_get_path('module', 'ckeditor') . '/js/plugins/drupalimage/image.png',
    
    );
  }
  
    /**
   * {@inheritdoc}filebrowserBrowseUrl
   */
  public function getButtons() {
    return array(
      'elFinderImage' => array(
        'label' => t('elFinder Image'),
        'image' => drupal_get_path('module', 'ckeditor') . '/js/plugins/drupalimage/image.png',
      ),
    );
  }
  
  

}
