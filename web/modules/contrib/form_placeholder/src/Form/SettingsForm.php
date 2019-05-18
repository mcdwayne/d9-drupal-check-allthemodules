<?php

namespace Drupal\form_placeholder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_placeholder_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('form_placeholder.settings')
      ->set('included_selectors', $form_state->getValue('included_selectors'))
      ->set('excluded_selectors', $form_state->getValue('excluded_selectors'))
      ->set('required_indicator', $form_state->getValue('required_indicator'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['form_placeholder.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('form_placeholder.settings');

    $form['selectors'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Visibility settings'),
    ];

    $form['selectors']['included_selectors'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Include text fields matching the pattern'),
      '#description' => $this->t('CSS selectors (one per line).'),
      '#default_value' => $config->get('included_selectors'),
    ];

    $form['selectors']['excluded_selectors'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Exclude text fields matching the pattern'),
      '#description' => $this->t('CSS selectors (one per line).'),
      '#default_value' => $config->get('excluded_selectors'),
    ];

    $form['selectors']['examples'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Examples'),
    ];

    $form['selectors']['examples']['content'] = [
      '#type' => 'table',
      '#header' => [$this->t('CSS selector'), $this->t('Description')],
      '#rows' => [
        [
          'input, textarea',
          $this->t('Use all single line text fields and textareas on site.'),
        ],
        [
          '.your-form-class *',
          $this->t('Use all text fields in given form class.'),
        ],
        [
          '#your-form-id *',
          $this->t('Use all text fields in given form id.'),
        ],
        [
          '#your-form-id *:not(textarea)',
          $this->t('Use all single line text fields but not textareas in given form id.'),
        ],
        [
          '#your-form-id input:not(input[type=password])',
          $this->t('Use all single line text fields but not password text fields in given form id.'),
        ],
      ],
    ];

    $form['required_indicator'] = [
      '#type' => 'radios',
      '#title' => $this->t('Required field marker'),
      '#options' => [
        'append' => $this->t('Append star after text field'),
        'leave' => $this->t('Leave star inside placeholder'),
        'remove' => $this->t('Remove star'),
        'text' => $this->t('Instead of star append text "(required)" to placeholder'),
        'optional' => $this->t('Append text "(optional)" to non-required fields'),
      ],
      '#default_value' => $config->get('required_indicator'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
