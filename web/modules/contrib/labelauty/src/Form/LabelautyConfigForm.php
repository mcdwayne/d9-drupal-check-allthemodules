<?php

namespace Drupal\labelauty\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Labelauty.
 */
class LabelautyConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'labelauty_form_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['labelauty.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('labelauty.settings');

    $form['config'] = [
      '#type' => 'vertical_tabs',
    ];

    $form['form'] = [
      '#type' => 'details',
      '#title' => $this->t('Forms'),
      '#description' => $this->t('Configure on which forms the Labelauty plugin should be used. This configuration is ignored on Labelauty enabled checkbox and radio fields on entities.'),
      '#group' => 'config',
    ];

    $form['form']['form'] = [
      '#type' => 'radios',
      '#title' => $this->t('Show Labelauty on specific forms'),
      '#options' => [
        LABELAUTY_FORM_NOTLISTED => $this->t('All forms except those listed'),
        LABELAUTY_FORM_LISTED => $this->t('Only the listed forms'),
      ],
      '#default_value' => $config->get('form'),
    ];

    $form['form']['form_id'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Forms'),
      '#description' => $this->t('Enter one form id per line.'),
      '#title_display' => 'invisible',
      '#default_value' => $config->get('form_id'),
    ];

    $form['form']['form_radio'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Labelauty for radio elements'),
      '#default_value' => $config->get('form_radio'),
    ];

    $form['form']['form_checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Labelauty for checkbox elements'),
      '#default_value' => $config->get('form_checkbox'),
    ];

    $form['label'] = [
      '#type' => 'details',
      '#title' => $this->t('Labels'),
      '#group' => 'config',
    ];

    $form['label']['label_hide'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide labels'),
      '#description' => $this->t('Hide the checked / unchecked labels on elements without a label, displaying only an icon.'),
      '#default_value' => $config->get('label_hide'),
    ];

    $form['label']['label_checked'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default checked label'),
      '#description' => $this->t('The default checked label on elements without a label.'),
      '#default_value' => $config->get('label_checked'),
      '#states' => [
        'visible' => [
          ':input[name="label_hide"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['label']['label_unchecked'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default unchecked label'),
      '#description' => $this->t('The default unchecked label on elements without a label.'),
      '#default_value' => $config->get('label_unchecked'),
      '#states' => [
        'visible' => [
          ':input[name="label_hide"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('labelauty.settings');
    $config->set('form', $form_state->getValue('form'));
    $config->set('form_id', $form_state->getValue('form_id'));
    $config->set('form_radio', $form_state->getValue('form_radio'));
    $config->set('form_checkbox', $form_state->getValue('form_checkbox'));
    $config->set('label_hide', $form_state->getValue('label_hide'));
    $config->set('label_checked', $form_state->getValue('label_checked'));
    $config->set('label_unchecked', $form_state->getValue('label_unchecked'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
