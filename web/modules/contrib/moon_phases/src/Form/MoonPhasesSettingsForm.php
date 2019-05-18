<?php

namespace Drupal\moon_phases\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MoonPhasesSettingsForm.
 */
class MoonPhasesSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'moon_phases_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['moon_phases.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('moon_phases.settings');

    $form['show_attribution'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show attribution'),
      '#description' => $this->t('Show the attribution for the moon phase images.'),
      '#default_value' => $config->get('show_attribution', 0),
    ];

    $form['new_moon'] = [
      '#type' => 'textarea',
      '#title' => $this->t('New Moon description'),
      '#default_value' => ($config->get('new_moon')) ? $config->get('new_moon') : MOON_PHASE_NEW_MOON,
    ];

    $form['waxing_crescent'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Waxing Crescent Moon description'),
      '#default_value' => ($config->get('waxing_crescent')) ? $config->get('waxing_crescent') : MOON_PHASE_WAXING_CRESCENT,
    ];

    $form['first_quarter'] = [
      '#type' => 'textarea',
      '#title' => $this->t('First Quarter Moon description'),
      '#default_value' => ($config->get('first_quarter')) ? $config->get('first_quarter') : MOON_PHASE_FIRST_QUARTER,
    ];

    $form['waxing_gibbous'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Waxing Gibbous Moon description'),
      '#default_value' => ($config->get('waxing_gibbous')) ? $config->get('waxing_gibbous') : MOON_PHASE_WAXING_GIBBOUS,
    ];

    $form['full_moon'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Full Moon description'),
      '#default_value' => ($config->get('full_moon')) ? $config->get('full_moon') : MOON_PHASE_FULL_MOON,
    ];

    $form['waning_gibbous'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Waning Moon description'),
      '#default_value' => ($config->get('waning_gibbous')) ? $config->get('waning_gibbous') : MOON_PHASE_WANING_GIBBOUS,
    ];

    $form['third_quarter'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Third Quarter Moon description'),
      '#default_value' => ($config->get('third_quarter')) ? $config->get('third_quarter') : MOON_PHASE_THIRD_QUARTER,
    ];

    $form['waning_crescent'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Waning Crescent Moon description'),
      '#default_value' => ($config->get('waning_crescent')) ? $config->get('waning_crescent') : MOON_PHASE_WANING_CRESCENT,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('moon_phases.settings')
      ->set('show_attribution', $form_state->getValue('show_attribution'))
      ->set('new_moon', $form_state->getValue('new_moon'))
      ->set('waxing_crescent', $form_state->getValue('waxing_crescent'))
      ->set('first_quarter', $form_state->getValue('first_quarter'))
      ->set('waxing_gibbous', $form_state->getValue('waxing_gibbous'))
      ->set('full_moon', $form_state->getValue('full_moon'))
      ->set('waning_gibbous', $form_state->getValue('waning_gibbous'))
      ->set('third_quarter', $form_state->getValue('third_quarter'))
      ->set('waning_crescent', $form_state->getValue('waning_crescent'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
