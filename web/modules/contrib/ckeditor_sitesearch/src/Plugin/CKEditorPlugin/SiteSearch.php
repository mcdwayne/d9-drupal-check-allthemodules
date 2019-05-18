<?php

namespace Drupal\ckeditor_sitesearch\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "Site Search" plugin.
 *
 * @CKEditorPlugin(
 *   id = "sitesearch",
 *   label = @Translation("Search"),
 *   module = "ckeditor_sitesearch"
 * )
 */
class SiteSearch extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface, CKEditorPluginContextualInterface{

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    if ($library_path = libraries_get_path('sitesearch')) {
      return $library_path . '/plugin.js';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return array();
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
    return array(
      'sitesearch' => array(
        'label' => t('Site Search'),
        'image' => libraries_get_path('sitesearch') . '/icons/sitesearch.png',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    $settings = $editor->getSettings();

    if (isset($settings['plugins']['sitesearch'])) {
      return $editor->getSettings()['plugins']['sitesearch']['enable'];
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $settings = $editor->getSettings()['plugins']['sitesearch'];

    return array(
      'search' => array(
        'enable' => isset($settings['enable']) ? $settings['enable'] : FALSE,
        'search_path' => isset($settings['search_path']) ? $settings['search_path'] : '/search',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $editor_settings = $editor->getSettings();
    if (isset($editor_settings['plugins']['sitesearch'])) {
      $settings = $editor_settings['plugins']['sitesearch'];
    }

    $form['#attached']['library'][] = 'ckeditor_sitesearch/ckeditor_sitesearch.admin';

    $form['enable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Site Search.'),
      '#default_value' => isset($settings['enable']) ? $settings['enable'] : FALSE,
    );

    $form['search_path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Search Path'),
      '#default_value' => isset($settings['search_path']) ? $settings['search_path'] : '/search',
      '#description' => $this->t('Enter your website\'s search path e.g., "/search"'),
    );

    return $form;
  }
}
