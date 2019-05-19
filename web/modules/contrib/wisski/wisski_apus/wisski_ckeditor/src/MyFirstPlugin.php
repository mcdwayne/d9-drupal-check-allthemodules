<?php

/**
 * @file
 * Contains \Drupal\wisski_ckeditor\Plugin\CKEditorPlugin\MyFirstPlugin.
 */

namespace Drupal\wisski_ckeditor\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "stylescombo" plugin.
 *
 * @CKEditorPlugin(
 *   id = "stylescombo",
 *   label = @Translation("Styles dropdown")
 * )
 */
class MyFirstPlugin extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface {


  /**
   * {@inheritdoc}
   */
  public function getFile() {
    // This plugin is already part of Drupal core's CKEditor build.
    return  drupal_get_path('module', 'wisski_ckeditor') . '/js/plugins/myfirstplugin/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $config = array();
    $settings = $editor->getSettings();
    if (!isset($settings['plugins']['stylescombo']['styles'])) {
      return $config;
    }
    $styles = $settings['plugins']['stylescombo']['styles'];
    $config['stylesSet'] = $this->generateStylesSetSetting($styles);
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return array(
      'MyButton' => array(
        'label' => t('My brand new button'),
        'image' => drupal_get_path('module', 'wisski_ckeditor') . '/js/plugins/myfirstplugin/plugin.png'
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    return $form;
  }

}
