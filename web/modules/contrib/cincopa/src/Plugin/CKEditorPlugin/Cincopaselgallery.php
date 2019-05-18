<?php

namespace Drupal\cincopa\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "cincopaselgallery" plugin.
 *
 * @CKEditorPlugin(
 *   id = "cincopaselgallery",
 *   label = @Translation("Cincopa Gallery"),
 *   module = "cincopa"
 * )
 */
class Cincopaselgallery extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface {

  
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    return $form;
  }

  /**
   * isInternal - Return is this plugin internal or not.
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::isInternal().
   */
  public function isInternal( ) {
    return FALSE;
  }

  /**
   * getFile - Return absolute path to plugin.js file.
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'cincopa') . '/js/plugins/cincopaselgallery/plugin.js';
  }

  /**
   * getLibraries - Return dependency libraries, so those get loaded.
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return array();
  }

  /**
   * getConfig - Return configuration array
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array();
  }

  /**
   * getButtons - Return button info.
   * {@inheritdoc}
   */
  public function getButtons() {
    return array(
      'Cincopaselgallery' => array(
        'label' => t('Cincopa'),
        'image_alternative' => [
          '#type' => 'inline_template',
          '#template' => '<a href="#" role="button" aria-label="{{ styles_text }}"><span class="ckeditor-button-dropdown">{{ styles_text }}<span class="ckeditor-button-arrow"></span></span></a>',
          '#context' => [
            'styles_text' => t('Cincopa'),
          ],
        ],
      ),
    );
  }
}