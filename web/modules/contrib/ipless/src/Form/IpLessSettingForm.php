<?php

namespace Drupal\ipless\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * IpLessSettingForm.
 */
class IpLessSettingForm {

  use StringTranslationTrait;

  /**
   * Form Alter.
   */
  public function formAlter_system_performance_settings(&$form, FormStateInterface $form_state) {
    $config = \Drupal::config('system.performance');

    $form['bandwidth_optimization']['ipless'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Less CSS'),
      '#tree'  => TRUE
    ];

    $form['bandwidth_optimization']['ipless']['enabled'] = [
      '#type'          => 'checkbox',
      '#default_value' => $config->get('ipless.enabled'),
      '#title'         => t('Less compilation enabled'),
    ];

    $form['bandwidth_optimization']['ipless']['modedev'] = [
      '#type'          => 'checkbox',
      '#default_value' => $config->get('ipless.modedev'),
      '#title'         => t('Less developper mode'),
      '#description'   => t('Compile Less file all the time.')
    ];

    $form['bandwidth_optimization']['ipless']['sourcemap'] = [
      '#type'          => 'checkbox',
      '#default_value' => $config->get('ipless.sourcemap'),
      '#title'         => t('Enable SourceMap'),
    ];

    $form['#submit'][] = static::class . '::checkSettings';
  }

  /**
   * Form Callback : Check settings.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public static function checkSettings(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('system.performance');

    $form_state->cleanValues();   
    $config->set('ipless', $form_state->getValue('ipless'));
    $config->save();
  }

}
