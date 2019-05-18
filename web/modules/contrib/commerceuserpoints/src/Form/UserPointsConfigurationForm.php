<?php

namespace Drupal\commerce_user_points\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class UserPointsConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_user_points_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_user_points.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('commerce_user_points.settings');

    // User registration points.
    $form['user_register_points'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User registration points'),
      '#attributes' => [
        'type' => 'number',
      ],
      '#default_value' => $config->get('user_register_points'),
    ];

    // Default Percentage.
    $form['order_point_discount'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Percentage'),
      '#attributes' => [
        'type' => 'number',
      ],
      '#default_value' => $config->get('order_point_discount'),
    ];

    $form['date_discount'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#description' => $this->t('Select day on which specific discount is applicable.'),
      '#open' => TRUE,
    ];

    // @todo - make field dynamic
    $options = [
      '1' => $this->t('Monday'),
      '2' => $this->t('Tuesday'),
      '3' => $this->t('Wednesday'),
      '4' => $this->t('Thurday'),
      '5' => $this->t('Friday'),
      '6' => $this->t('Saturday'),
      '7' => $this->t('Sunday'),
    ];

    $form['date_discount']['day_point_discount'] = [
      '#type' => 'select',
      '#title' => $this->t('Day'),
      '#options' => $options,
      '#description' => $this->t('Add points to select.'),
      '#default_value' => $config->get('day_point_discount'),
    ];

    $form['date_discount']['date_point_discount'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Day Percentage'),
      '#attributes' => [
        'type' => 'number',
      ],
      '#default_value' => $config->get('date_point_discount'),
    ];

    $form['maximum_value']['threshold_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Threshold value'),
      '#description' => $this->t('Minimum level of points to use.'),
      '#default_value' => $config->get('threshold_value'),
    ];

    $form['discout_price'] = [
      '#type' => 'radios',
      '#title' => $this->t('Discount Applicable On'),
      '#required' => TRUE,
      '#description' => $this->t('Select the option on which you want to calculate the discount.'),
      '#options' => [
        $this->t('Order Total'),
        $this->t('Order Subtotal'),
      ],
      '#default_value' => $config->get('discout_price'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('commerce_user_points.settings')
      ->set('user_register_points', $values['user_register_points'])
      ->set('order_point_discount', $values['order_point_discount'])
      ->set('day_point_discount', $values['day_point_discount'])
      ->set('date_point_discount', $values['date_point_discount'])
      ->set('threshold_value', $values['threshold_value'])
      ->set('discout_price', $values['discout_price'])
      ->save();

    drupal_set_message($this->t('Configuration Submitted Successfully'));
  }

}
