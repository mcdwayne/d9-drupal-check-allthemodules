<?php

namespace Drupal\google_directions\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Provides a 'Google Directions' Form.
 */
class GoogleDirectionsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'GoogleDirectionsForm';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $google_api_key = $this->config('google_directions.settings')->get('google_api_key');

    $form['#attached']['library'][] = 'google_directions/google_directions.lib';
    $form['#attached']['drupalSettings']['api_key_val'] = $google_api_key;

    $form['origin'] = array(
      '#type' => 'textfield',
      '#placeholder' => $this->t('From'),
      '#required' => TRUE,
      '#prefix' => '<div class="details-row">',
      '#weight' => 0,
    );
    $form['swap'] = array(
      '#markup' => '<a id="edit-swap" class="swap-link btn btn-green">Swap</a>',
      '#weight' => 1,
    );
    $form['destination'] = array(
      '#type' => 'textfield',
      '#placeholder' => $this->t('To'),
      '#suffix' => '</div>',
      '#weight' => 2,
    );
    $form['google-directions-transit_time'] = array(
      '#type' => 'radios',
      '#options' => array('depart' => $this->t('Depart'), 'arrive' => $this->t('Arrive')),
      '#prefix' => '<div class="row-datetime"><div class="input-group transit-time">',
      '#default_value' => 'depart',
      '#weight' => 3,
      '#suffix' => '</div>',
    );
    $form['google-directions-date'] = array(
      '#type' => 'datetime',
      '#date_date_element' => 'date',
      '#date_time_element' => 'text',
      '#date_time_format' => 'h:i A',
      '#default_value' => DrupalDateTime::createFromTimestamp(time()),
      '#description' => $this->t('Please enter time in 12-hour format as - "04:35 PM"'),
      '#weight' => 4,
    );
    $form['getdirection'] = array(
      '#type' => 'button',
      '#value' => $this->t('Go'),
      '#weight' => 6,
      '#prefix' => '<div class="date-time-go">',
      '#suffix' => '</div></div>',
    );
    $form['ajax_response'] = array(
      '#prefix' => '<div id="directions-html" class="no-result">',
      '#suffix' => '</div>',
      '#weight' => 7,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
