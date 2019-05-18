<?php

/**
 * @file
 * Administrative class form for the outdatedbrowser module.
 *
 * Contains \Drupal\outdatedbrowser\Form\OutdatedbrowserSettingsForm.
 */

namespace Drupal\outdatedbrowser\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * General configuration form for controlling the outdatedbrowser behaviour..
 */
class OutdatedbrowserSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'outdatedbrowser_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array('outdatedbrowser.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load all settings.
    $config = $this->config('outdatedbrowser.settings');

    $form['outdatedbrowser_compression_type'] = array(
      '#title' => t('Outdated Browser library compression type'),
      '#type' => 'select',
      '#options' => array(
        'minified' => t('Minified (production)'),
        'source' => t('Uncompressed (development version)'),
      ),
      '#default_value' => $config->get('compression_type'),
      '#required' => TRUE,
    );

    $form['outdatedbrowser_bgcolor'] = array(
      '#title' => t('Outdated Browser message: background color'),
      '#description' => t('The background color of the displayed browser upgrade advice. Must be a valid hexadecimal CSS color string, such as @hex.', array('@hex' => '#f25648')),
      '#type' => 'color',
      '#default_value' => $config->get('bgcolor'),
      '#required' => TRUE,
    );

    $form['outdatedbrowser_color'] = array(
      '#title' => t('Outdated Browser message: font color'),
      '#description' => t('The font color of the displayed browser upgrade advice. Must be a valid hexadecimal CSS color string, such as @hex.', array('@hex' => '#ffffff')),
      '#type' => 'color',
      '#default_value' => $config->get('color'),
      '#required' => TRUE,
    );

    $form['outdatedbrowser_lowerthan'] = array(
      '#title' => t('Outdated Browser message: targeting browser'),
      '#description' => t('The targeting browser of the displayed browser upgrade advice, either a CSS property or an Internet Explorer version.'),
      '#type' => 'textfield',
      '#default_value' => $config->get('lowerthan'),
      '#required' => TRUE,
    );
    
    $form['outdatedbrowser_lang_files_path'] = array(
      '#title' => t('Outdated Browser message files'),
      '#description' => t('Language files location - you can use the outdatedbrowser library path or your own path with additional language files added and HTML/css adapted.'),
      '#type' => 'textfield',
      '#default_value' => $config->get('lang_files_path', 'libraries/outdated-browser/outdatedbrowser/lang'),
      '#required' => TRUE,
    );    

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get config factory and load all settings.
    $config = $this->configFactory->getEditable('outdatedbrowser.settings');

    $compression_type_changed = $config->get('compression_type') != $form_state->getValue('outdatedbrowser_compression_type');
    if ($compression_type_changed) {
      // Invalidate the library_info cache.
      \Drupal::cache('discovery')->invalidate('library_info');
    }

    $config
      ->set('compression_type', $form_state->getValue('outdatedbrowser_compression_type'))
      ->set('bgcolor', $form_state->getValue('outdatedbrowser_bgcolor'))
      ->set('color', $form_state->getValue('outdatedbrowser_color'))
      ->set('lowerthan', $form_state->getValue('outdatedbrowser_lowerthan'))
      ->set('lang_files_path', $form_state->getValue('outdatedbrowser_lang_files_path'));

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
