<?php

namespace Drupal\multistep_submit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\multistep_submit\Form
 */
class SettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multistep_submit_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'multistep_submit_form.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('multistep_submit_form.settings');
    // Container for our repeating fields.
    $form['labels'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Labels of steps button'),
      '#prefix' => '<div id="labels_fieldset_wrapper">',
      '#suffix' => '</div>',
    ];

    $form['labels']['next_btn'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Next button'),
        '#default_value' => !empty($config->get('multistep_submit_next_btn')) ? $config->get('multistep_submit_next_btn') : t('Next'),
    ];

    $form['labels']['previous_btn'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Previous button'),
        '#default_value' => !empty($config->get('multistep_submit_previous_btn')) ? $config->get('multistep_submit_previous_btn') : t('Previous'),
    ];

    $form['labels']['cancel_btn'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Cancel button'),
        '#default_value' => !empty($config->get('multistep_submit_cancel_btn')) ? $config->get('multistep_submit_cancel_btn') : t('Cancel'),
    ];

    $form['labels']['finish_btn'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Finish button'),
        '#default_value' => !empty($config->get('multistep_submit_finish_btn')) ? $config->get('multistep_submit_finish_btn') : t('Finish'),
    ];

      $form['orientation'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Steps Orientation'),
          '#prefix' => '<div id="labels_fieldset_wrapper">',
          '#suffix' => '</div>',
      ];
      $form['orientation']['orientation'] = [
          '#type' => 'select',
          '#title' => $this->t('Choose Orientation'),
          '#options' => ['horizontal' => t('Horizontal'), 'vertical' => T('Vertical')],
          '#default_value' => !empty($config->get('multistep_submit_orientation')) ? $config->get('multistep_submit_orientation') : 'horizontal',
      ];

      $form['transition'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Transition Effects'),
          '#prefix' => '<div id="labels_fieldset_wrapper">',
          '#suffix' => '</div>',
      ];
      $form['transition']['effect'] = [
          '#type' => 'select',
          '#title' => $this->t('Choose Effects'),
          '#options' => ['fade' => T('Fade'), 'slide' => T('Slide down/up transition'), 'slideLeft' => T('Slide left transition')],
          '#default_value' => !empty($config->get('multistep_submit_transition_effects')) ? $config->get('multistep_submit_transition_effects') : 'slideLeft',
      ];

    // Submit button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
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

    $form_values = $form_state->getValues();
    $config = \Drupal::configFactory()->getEditable('multistep_submit_form.settings');
    
    $config->set('multistep_submit_next_btn', $form_values['next_btn']);
    $config->set('multistep_submit_cancel_btn', $form_values['cancel_btn']);
    $config->set('multistep_submit_finish_btn', $form_values['finish_btn']);
    $config->set('multistep_submit_previous_btn', $form_values['previous_btn']);
    $config->set('multistep_submit_transition_effects', $form_values['effect']);
    $config->set('multistep_submit_orientation', $form_values['orientation']);
    $config->save();
    drupal_set_message(t('Your configuration is saved'), 'status');

  }

}
