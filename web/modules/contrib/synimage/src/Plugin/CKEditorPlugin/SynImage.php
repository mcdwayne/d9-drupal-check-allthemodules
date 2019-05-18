<?php

namespace Drupal\synimage\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "synimage" plugin.
 *
 * @CKEditorPlugin(
 *   id = "synimage",
 *   label = @Translation("Image Synbox"),
 *   module = "synimage"
 * )
 */
class SynImage extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface {

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::isInternal().
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'synimage') . '/js/plugins/synimage/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [
      'core/drupal.ajax',
      'synimage/default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $config = [];
    $settings = $editor->getSettings();
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'SynImage' => [
        'label' => t('Image insert'),
        'image' => drupal_get_path('module', 'synimage') . '/js/plugins/synimage/icons/synimage.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $config = [
      'upload' => FALSE,
      'watermark' => FALSE,
    ];
    $settings = $editor->getSettings();
    if (isset($settings['plugins']['synimage'])) {
      $sett = $settings['plugins']['synimage'];
      if (isset($sett['upload'])) {
        $config['upload'] = $sett['upload'];
      }
      if (isset($sett['watermark'])) {
        $config['watermark'] = $sett['watermark'];
      }
    }
    $form['upload'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow upload'),
      '#required' => FALSE,
      '#default_value' => $config['upload'],
      '#description' => 'Позволять загружать картинки из виджета, лучше не юзать',
    ];
    $form['watermark'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow watermark'),
      '#required' => FALSE,
      '#default_value' => $config['watermark'],
      '#description' => 'Дать возможность использвать watermark,
      должет существовать соотвествующий стиль',
    ];
    return $form;
  }

}
