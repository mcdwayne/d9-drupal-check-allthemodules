<?php

namespace Drupal\float_labels\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm
 *
 * @package Drupal\float_labels\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'float_labels_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('float_labels.settings')
      ->set('included_forms', $form_state->getValue('included_forms'))
      ->set('excluded_forms', $form_state->getValue('excluded_forms'))
      ->set('included_selectors', $form_state->getValue('included_selectors'))
      ->set('excluded_selectors', $form_state->getValue('excluded_selectors'))
      ->set('mark_required', $form_state->getValue('mark_required'))
      ->set('select_field_default_value', $form_state->getValue('select_field_default_value'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['float_labels.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('float_labels.settings');

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<p>Float labels is configured first at the form level by selecting form IDs. Then, within each form it uses the provided CSS selectors to include and exclude specific fields as required.</p>'),
    ];

    $form['forms'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Forms'),
    ];

    $form['forms']['included_forms'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Included forms'),
      '#description' => $this->t('A list of form IDs to match, one per line. You may also use regex by starting and ending the line with "/"'),
      '#default_value' => $config->get('included_forms'),
    ];

    $form['forms']['excluded_forms'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Excluded forms'),
      '#description' => $this->t('A list of form IDs to exclude from the matches, one per line. You may also use regex by starting and ending the line with "/"'),
      '#default_value' => $config->get('excluded_forms'),
    ];

    $form['selectors'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('CSS selectors'),
    ];

    $form['selectors']['included_selectors'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Included selectors'),
      '#description' => $this->t('A list of CSS selectors to specify which fields within the form to include. Any valid CSS selector may be used, including "*".'),
      '#default_value' => $config->get('included_selectors'),
    ];

    $form['selectors']['excluded_selectors'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Excluded selectors'),
      '#description' => $this->t('A list of CSS selectors to specify which fields within the matched selectors to exclude. Any valid CSS selector may be used, including "*".'),
      '#default_value' => $config->get('excluded_selectors'),
    ];

    $form['options'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Options'),
    ];

    $form['options']['mark_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mark required fields'),
      '#description' => $this->t('Mark fields that are required with a "*"'),
      '#default_value' => $config->get('mark_required'),
    ];

    $form['options']['select_field_default_value'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Replace default select field option with its label.'),
      '#default_value' => $config->get('select_field_default_value'),
    ];

    return parent::buildForm($form, $form_state);
  }
  
}
