<?php

namespace Drupal\ckeditor_quicktable\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginButtonsInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines the "QuickTable" plugin.
 *
 * @CKEditorPlugin(
 *   id = "quicktable",
 *   label = @Translation("QuickTable")
 * )
 */
class QuickTable extends PluginBase implements CKEditorPluginInterface, CKEditorPluginButtonsInterface {
  
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return ['panelbutton'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [];
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
  public function getFile() {
    return drupal_get_path('module', 'ckeditor_quicktable') . '/js/plugins/quicktable/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'QuickTable' => [
        'label' => $this->t('Quick Table'),
        'image' => drupal_get_path('module', 'ckeditor_quicktable') . '/js/plugins/quicktable/icons/quicktable.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

}
