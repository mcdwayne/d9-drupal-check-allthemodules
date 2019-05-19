<?php

namespace Drupal\sinceago\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SinceAgo.
 */
class SinceAgo extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sinceago.sinceago',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'since_ago';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sinceago.sinceago');

    $form['sinceago_node'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use sinceago for node creation dates'),
      '#default_value' => $config->get('sinceago_node'),
    ];
    $form['sinceago_comment'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use sinceago for comment creation/changed dates'),
      '#default_value' => $config->get('sinceago_comment'),
    ];
    $form['sinceago_script_setting'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('OVERRIDE SINCEAGO SCRIPT SETTINGS'),
    ];
    $form['sinceago_script_setting']['refresh_sinceago'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Refresh Sinceago dates after'),
      '#maxlength' => 64,
      '#size' => 64,
      '#description' => $this->t('Sinceago can update its dates without a page refresh at this interval. Leave blank or set to zero to never refresh Sinceago dates.'),
      '#default_value' => $config->get('refresh_sinceago'),
    ];
    $form['sinceago_script_setting']['allow_future'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow future dates'),
      '#default_value' => $config->get('allow_future'),
    ];
    $form['sinceago_script_setting']['titeattr_sinceago'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Set the "title" attribute of Sinceago dates to a locale-sensitive date'),
      '#description' => $this->t('If this is disabled (the default) then the "title" attribute defaults to the original date that the sinceago script is replacing.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('donotuse_sinceago'),
    ];
    $form['sinceago_script_setting']['donotuse_sinceago'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Do not use sinceago dates after'),
      '#description' => $this->t('Leave blank or set to zero to always use sinceago dates.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('donotuse_sinceago'),
    ];
    $form['string_settings'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('OVERRIDE SINCEAGO STRING SETTINGS'),
    ];
    $form['string_settings']['prefixAgo'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix ago'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('strings.prefixAgo'),
    ];
    $form['string_settings']['prefixFromNow'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix from now'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('strings.prefixFromNow'),
    ];
    $form['string_settings']['suffixAgo'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Suffix ago'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('strings.suffixAgo'),
    ];
    $form['string_settings']['suffixFromNow'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Suffix from now'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('strings.suffixFromNow'),
    ];
    $form['string_settings']['inPast'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Inpast'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('strings.inPast'),
    ];
    $form['string_settings']['seconds'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Seconds'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('strings.seconds'),
    ];
    $form['string_settings']['minute'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minute'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('strings.minute'),
    ];
    $form['string_settings']['minutes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minutes'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('strings.minutes'),
    ];
    $form['string_settings']['hour'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hour'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('strings.hour'),
    ];
    $form['string_settings']['hours'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hours'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('strings.hours'),
    ];
    $form['string_settings']['day'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Day'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('strings.day'),
    ];
    $form['string_settings']['days'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Days'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('strings.days'),
    ];
    $form['string_settings']['month'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Month'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('strings.month'),
    ];
    $form['string_settings']['months'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Months'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('strings.months'),
    ];
    $form['string_settings']['year'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Year'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('strings.year'),
    ];
    $form['string_settings']['years'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Years'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('strings.years'),
    ];
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

    $this->config('sinceago.sinceago')
      ->set('allow_future', $form_state->getValue('allow_future'))
      ->set('sinceago_node', $form_state->getValue('sinceago_node'))
      ->set('sinceago_comment', $form_state->getValue('sinceago_comment'))
      ->set('time_element', $form_state->getValue('time_element'))
      ->set('refresh_sinceago', $form_state->getValue('refresh_sinceago'))
      ->set('titeattr_sinceago', $form_state->getValue('titeattr_sinceago'))
      ->set('donotuse_sinceago', $form_state->getValue('donotuse_sinceago'))
      ->set('strings.prefixAgo', $form_state->getValue('prefixAgo'))
      ->set('strings.prefixFromNow', $form_state->getValue('prefixFromNow'))
      ->set('strings.suffixAgo', $form_state->getValue('suffixAgo'))
      ->set('strings.suffixFromNow', $form_state->getValue('suffixFromNow'))
      ->set('strings.inPast', $form_state->getValue('inPast'))
      ->set('strings.seconds', $form_state->getValue('seconds'))
      ->set('strings.minute', $form_state->getValue('minute'))
      ->set('strings.minutes', $form_state->getValue('minutes'))
      ->set('strings.hour', $form_state->getValue('hour'))
      ->set('strings.hours', $form_state->getValue('hours'))
      ->set('strings.day', $form_state->getValue('day'))
      ->set('strings.days', $form_state->getValue('days'))
      ->set('strings.month', $form_state->getValue('month'))
      ->set('strings.months', $form_state->getValue('months'))
      ->set('strings.year', $form_state->getValue('year'))
      ->set('strings.years', $form_state->getValue('years'))
      ->save();
  }

}
