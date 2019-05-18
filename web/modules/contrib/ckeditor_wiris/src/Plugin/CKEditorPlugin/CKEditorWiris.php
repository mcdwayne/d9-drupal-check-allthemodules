<?php

namespace Drupal\ckeditor_wiris\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "Wiris (MathType/ChemType)" plugin.
 *
 * @CKEditorPlugin(
 *   id = "ckeditor_wiris",
 *   label = @Translation("CKEditor Wiris"),
 *   module = "ckeditor_wiris"
 * )
 */
class CKEditorWiris extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return $this->getPluginPath() . '/plugin.js';
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
  public function getButtons() {
    return [
      'ckeditor_wiris_formulaEditor' => [
        'label' => t('MathType'),
        'image' => $this->getPluginPath() . '/icons/formula.png',
      ],
      'ckeditor_wiris_formulaEditorChemistry' => [
        'label' => t('ChemType'),
        'image' => $this->getPluginPath() . '/icons/chem.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

  /**
   * Return the plugin folder path.
   */
  public function getPluginPath() {
    return drupal_get_path('module', 'ckeditor_wiris') . '/js/plugins/ckeditor_wiris';
  }

}