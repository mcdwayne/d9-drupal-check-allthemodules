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
class JRejectAlternativesSettings extends ConfigFormBase {

  /**
   *
   */
  public function getFormId() {
    return 'jreject_alternatives_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['jreject.alternatives.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('jreject.alternatives.settings');

    $form = array();

    $form = array();

    $form['intro'] = [
      '#type' => 'item',
      '#markup' => '<p>Select which browsers you\'d like to present as upgrades in the modal window.
                      It is recommended that you choose at least three.</p>',
    ];

    $form['jreject_msie'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('jreject_msie'),
      '#title' => t('Internet Explorer'),
    ];

    $form['jreject_firefox'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('jreject_firefox'),
      '#title' => t('Firefox'),
    ];

    $form['jreject_safari'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('jreject_safari'),
      '#title' => t('Safari'),
    ];

    $form['jreject_opera'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('jreject_opera'),
      '#title' => t('Opera'),
    ];

    $form['jreject_chrome'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('jreject_chrome'),
      '#title' => t('Chrome'),
    ];

    $form['jreject_gcf'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('jreject_gcf'),
      '#title' => t('Google Chrome Frame'),
      '#description' => t('If selected, this option will only appear for users of Internet Explorer.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration
    $this->configFactory->getEditable('jreject.alternatives.settings')
      ->set('jreject_msie', $form_state->getValue('jreject_msie'))
      ->set('jreject_firefox', $form_state->getValue('jreject_firefox'))
      ->set('jreject_safari', $form_state->getValue('jreject_safari'))
      ->set('jreject_opera', $form_state->getValue('jreject_opera'))
      ->set('jreject_chrome', $form_state->getValue('jreject_chrome'))
      ->set('jreject_gcf', $form_state->getValue('jreject_gcf'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  public static function getAlternativesBrowers() {
    $config = \Drupal::config('jreject.alternatives.settings');
    $data = $config->getRawData();

    $out = array();
    foreach ($data as $browser => $enabled) {
      if ($enabled) {
        $out[] = substr($browser, 8);
      }
    }
    return $out;
  }
}
