<?php

namespace Drupal\virtual_keyboard\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'virtual_keyboard_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('virtual_keyboard.settings')
      ->set('included_selectors', $form_state->getValue('included_selectors'))
      ->set('excluded_selectors', $form_state->getValue('excluded_selectors'))
      ->set('method', $form_state->getValue('method'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['virtual_keyboard.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('virtual_keyboard.settings');

    $form['selectors'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Visibility settings'),
    ];

    $form['selectors']['included_selectors'] = [
      '#type' => 'textarea',
      '#title' => t('Include text fields matching the pattern'),
      '#description' => t('CSS selectors (one per line).'),
      '#default_value' => $config->get('included_selectors'),
    ];

    $form['selectors']['excluded_selectors'] = [
      '#type' => 'textarea',
      '#title' => t('Exclude text fields matching the pattern'),
      '#description' => t('CSS selectors (one per line).'),
      '#default_value' => $config->get('excluded_selectors'),
    ];

    $form['selectors']['examples'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => t('Examples'),
    ];

    $rows = [
      ['input, textarea', t('Use all single line text fields and textareas on site.')],
      ['.your-form-class *', t('Use all text fields in given form class.')],
      ['#your-form-id *', t('Use all text fields in given form id.')],
      ['#your-form-id *:not(textarea)', t('Use all single line text fields but  not textareas in given form id.')],
      ['#your-form-id input:not(input[type=password])', t('Use all single line text fields but not password text fields in given form id.')],
    ];
    $form['selectors']['examples']['content'] = [
      '#type' => 'table',
      '#header' => [t('CSS selector'), t('Description')],
      '#rows' => $rows,
    ];

    $form['method'] = [
      '#type' => 'radios',
      '#title' => t('Required field marker'),
      '#options' => [
        'icon' => t('Open keyboard by clicking the icon'),
        'focus' => t('Open keyboard when focusing on text field'),
        'icon_and_focus' => t('Both: by clicking the icon and focus'),
      ],
      '#default_value' => $config->get('method'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
