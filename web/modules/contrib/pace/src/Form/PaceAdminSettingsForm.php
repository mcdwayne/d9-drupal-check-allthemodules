<?php

/**
 * @file
 * Contains \Drupal\pace\Form\PaceAdminSettingsForm.
 */

namespace Drupal\pace\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements an test form.
 */
class PaceAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pace_admin_settings_form';
  }
  
  /**
   * {@inheritdoc}
   */  
  protected function getEditableConfigNames() {
    return ['pace.settings'];
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pace.settings');
    
    $form['pace_theme'] = array(
        '#title' => t('Select the theme that PACE should use'),
        '#description' => t('Pace comes with a lot of themes for the progress loader. Please select the one that you prefer. You can see them all here: http://github.hubspot.com/pace/docs/welcome/'),
        '#type' => 'radios',
        '#options' => array(
          'pace-theme-minimal' => 'minimal',
          'pace-theme-barber-shop' => 'barber',
          'pace-theme-big-counter' => 'big counter',
          'pace-theme-bounce' => 'bounce',
          'pace-theme-center-atom' => 'center atom',
          'pace-theme-center-circle' => 'center circle',
          'pace-theme-center-radar' => 'center radar',
          'pace-theme-center-simple' => 'center simple',
          'pace-theme-corner-indicator' => 'corner indicator',
          'pace-theme-fill-left' => 'fill left',
          'pace-theme-flash' => 'flash',
          'pace-theme-flat-top' => 'flat top',
          'pace-theme-loading-bar' => 'loading bar',
          'pace-theme-mac-osx' => 'mac osx',
        ),
        '#default_value' => $config->get('pace_theme') ?: 'pace-theme-minimal',
    );

    $form['pace_load_on_admin_enabled'] = array(
        '#title' => t('Load in administration pages.'),
        '#description' => t('PACE is disabled by default on administration pages. Check to enable'),
        '#type' => 'checkbox',
        '#default_value' => $config->get('pace_load_on_admin_enabled') ?: FALSE,
    );

    $form['pace_custom_color_enabled'] = array(
        '#title' => t('EXPERIMENTAL: Select custom color for PACE.'),
        '#description' => t('Override default PACE color. This setting will be outputted as CSS in your html head.<br>
          It will not be aggregated. NOT compatible with all PACE themes.'),
        '#type' => 'checkbox',
        '#default_value' => $config->get('pace_custom_color_enabled') ?: FALSE,
    );

    $form['pace_custom_color_value'] = array(
        '#title' => t('Set your color using HEX notation.'),
        '#description' => t('Do not include the # sign.'),
        '#type' => 'textfield',
        '#default_value' => $config->get('pace_custom_color_value') ?: array(),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('pace.settings')
      ->set('pace_theme', $form_state->getValue('pace_theme'))
      ->set('pace_load_on_admin_enabled', $form_state->getValue('pace_load_on_admin_enabled'))
      ->set('pace_custom_color_enabled', $form_state->getValue('pace_custom_color_enabled'))
      ->set('pace_custom_color_value', $form_state->getValue('pace_custom_color_value'))
      ->save();
  }


}