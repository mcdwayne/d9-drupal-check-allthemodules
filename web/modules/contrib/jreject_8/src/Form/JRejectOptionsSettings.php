<?php

/**
 * @file
 *Contains \Drupal\flag_limit\Form\flaglimitForm
 */

namespace Drupal\jreject\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Flag settings form.
 */
class JRejectOptionsSettings extends ConfigFormBase {

  /**
   *
   */
  public function getFormId() {
    return 'jreject_options_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['jreject.options.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('jreject.options.settings');

    $form = array();

    $form['jreject_close'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get("jreject_close"),
      '#title' => $this->t('Allow closing of modal window?'),
    ];

    $form['jreject_closeESC'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('jreject_closeESC'),
      '#title' => $this->t('Allow closing the modal window with the ESC key?'),
    ];

    $form['jreject_closeCookie'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('jreject_closeCookie'),
      '#title' => $this->t('Remember if the window was closed by setting a cookie?'),
    ];

    $form['jreject_overlayBgColor'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 7,
      '#default_value' => $config->get('jreject_overlayBgColor') ? $config->get('jreject_overlayBgColor') : '#000',
      '#title' => $this->t('Overlay background color'),
      '#description' => "Enter a custom overlay background color in hex.",
    ];

    $form['jreject_overlayOpacity'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 7,
      '#default_value' => $config->get('jreject_overlayOpacity') ? $config->get('jreject_overlayOpacity') : '0.8',
      '#title' => $this->t('Background opacity'),
      '#description' => "Enter a value from 0-1.",
    ];

    $form['jreject_fadeInTime'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 7,
      '#default_value' => $config->get('fadeInTime') ? $config->get('jreject_fadeInTime') : 'fast',
      '#title' => $this->t('Modal fade-in speed'),
      '#description' => $this->t("Enter a value ('slow','medium','fast' or integer in ms)"),
    ];

    $form['jreject_fadeOutTime'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 7,
      '#default_value' => $config->get('jreject_fadeOutTime') ? $config->get('jreject_fadeOutTime') : 'fast',
      '#title' => $this->t('Modal fade-out speed'),
      '#description' => $this->t("Enter a value ('slow','medium','fast' or integer in ms)"),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration
    $this->configFactory->getEditable('jreject.options.settings')
      ->set('jreject_close', $form_state->getValue('jreject_close'))
      ->set('jreject_closeESC', $form_state->getValue('jreject_closeESC'))
      ->set('jreject_closeCookie', $form_state->getValue('jreject_closeCookie'))
      ->set('jreject_overlayBgColor', $form_state->getValue('jreject_overlayBgColor'))
      ->set('jreject_overlayOpacity', $form_state->getValue('jreject_overlayOpacity'))
      ->set('jreject_fadeInTime', $form_state->getValue('jreject_fadeInTime'))
      ->set('jreject_fadeOutTime', $form_state->getValue('jreject_fadeOutTime'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  public static function getOptions() {
    $config = \Drupal::config('jreject.options.settings');
    $data = $config->getRawData();

    $out = array();

    foreach($data as $key => $opt) {
      $out[substr($key, 8)] = $opt;
    }

    return $out;
  }
}
