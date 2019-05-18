<?php

namespace Drupal\imgur\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "imgur" plugin.
 *
 * @CKEditorPlugin(
 *   id = "imgur",
 *   label = @Translation("Ck Imgur"),
 *   module = "imgur"
 * )
 */
class Imgur extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface{
  /**
   * Gets a path to module.
   *
   * @return string
   *   Full path to module.
   */
  private function path() {
    return drupal_get_path('module', 'imgur');
  }

  /**
   * Implements CKEditorPluginInterface::getLibraries().
   */
  public function getLibraries(Editor $editor) {
    return array();
  }

  /**
   * Implements CKEditorPluginInterface::isInternal().
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * Implements CKEditorPluginInterface::getFile().
   */
  public function getFile() {
    return $this->path() . "/js/plugins/imgur/plugin.js";
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return ['panelbutton'];
  }

  /**
   * Implements CKEditorPluginButtonsInterface::getButtons().
   */
  public function getButtons() {
      return array(
          'Imgur' => array(
              'label' => $this->t('Ck Imgur'),
              'image' => $this->path() . '/js/plugins/imgur/images/icon.png',
          ),
      );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    global $base_url;
    $settings = $editor->getSettings();

    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Imgur Client Id'),
      '#description' => $this->t('Enter the client id, Get it from https://api.imgur.com/oauth2/addclient, Use redirect is '.$base_url),
      '#default_value' => !empty($settings['plugins']['imgur']['client_id']) ? $settings['plugins']['imgur']['client_id'] : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $settings = $editor->getSettings();

    $config = [];

    if (!empty($settings['plugins']['imgur']['client_id'])) {
      $config['imgurClientId'] = $settings['plugins']['imgur']['client_id'];
    }

    return $config;
  }

}
