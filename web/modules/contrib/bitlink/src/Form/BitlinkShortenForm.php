<?php

namespace Drupal\bitlink\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to Shorten Bitlink Long URL.
 */
class BitlinkShortenForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bitlink_shorten_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $bitlink_config = $this->config('bitlink.settings');

    $form['long_url_field'] = [
      '#type' => 'details',
      '#title' => t('Long URL'),
      '#open' => TRUE,
    ];

    $form['long_url_field']['long_url'] = [
      '#type' => 'textarea',
      '#title' => t('Long URL'),
      '#default_value' => $bitlink_config->get('long_url'),
      '#description' => t('Valid Long URL that needs to be shortened.'),
      '#required' => TRUE,
    ];

    if ($form_state->hasValue('bitlink_response_data')) {
      $bitlink_response_data = $form_state->getValue('bitlink_response_data');

      $form['response_data'] = [
        '#type' => 'table',
        '#caption' => $this
          ->t('Bitlink Response'),
        '#header' => [
          $this->t('Key'),
          $this->t('Value'),
        ],
      ];

      $form['response_data'][] = [
        ['#markup' => 'Created At'],
        ['#markup' => $bitlink_response_data['data']['created_at']]
      ];

      $form['response_data'][] = [
        ['#markup' => 'ID'],
        ['#markup' => $bitlink_response_data['data']['id']]
      ];

      $form['response_data'][] = [
        ['#markup' => 'Link'],
        ['#markup' => $bitlink_response_data['data']['link']]
      ];

      $form['response_data'][] = [
        ['#markup' => 'Long URL'],
        ['#markup' => $bitlink_response_data['data']['long_url']]
      ];

      $form['response_data'][] = [
        ['#markup' => 'Reference Group'],
        ['#markup' => $bitlink_response_data['data']['references']->group]
      ];
    }

    if ($form_state->hasValue('bitlink_response_error')) {
      $response_error = $form_state->getValue('bitlink_response_error');
      $form['response_error'] = [
        '#type' => 'table',
        '#caption' => $this
          ->t('Bitlink Response'),
        '#header' => [
          $this->t('Error'),
        ],
      ];

      $form['response_error'][] = [
        ['#markup' => $response_error['data']['message']],
      ];
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Shorten URL'),
      '#button_type' => 'primary',
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
    $bitlink_service = \Drupal::service('bitlink.api_service');
    $long_url = $form_state->getValue('long_url');
    $response_data = $bitlink_service->shorten($long_url);

    if (!empty($response_data) && $response_data['status'] == 'success') {
      $form_state->setValue('bitlink_response_data', $response_data);
      $form_state->setRebuild();
    }
    else {
      $form_state->setValue('bitlink_response_error', $response_data);
      $form_state->setRebuild();
    }
  }

}
