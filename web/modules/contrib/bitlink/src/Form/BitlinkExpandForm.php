<?php

namespace Drupal\bitlink\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to Expand Bitlink Short URL.
 */
class BitlinkExpandForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bitlink_expand_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $bitlink_config = $this->config('bitlink.settings');

    $form['short_url_field'] = [
      '#type' => 'details',
      '#title' => t('Short URL'),
      '#open' => TRUE,
    ];

    $form['short_url_field']['short_url'] = [
      '#type' => 'textarea',
      '#title' => t('Short URL (Non-HTTP protocol URL)'),
      '#default_value' => $bitlink_config->get('Short_url'),
      '#description' => t('Valid Short URL that needs to be shortened. To expand a short URL http://bit.ly/ze6poY, only enter, bit.ly/ze6poY'),
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
    $short_url = $form_state->getValue('short_url');
    $response_data = $bitlink_service->expand($short_url);

    if (!empty($response_data)  && $response_data['status'] == 'success') {
      $form_state->setValue('bitlink_response_data', $response_data);
      $form_state->setRebuild();
    }
    else {
      $form_state->setValue('bitlink_response_error', $response_data);
      $form_state->setRebuild();
    }
  }

}
