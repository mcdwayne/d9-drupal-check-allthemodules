<?php

namespace Drupal\route_planner\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * The Address-Form.
 *
 * A form to input a address and get.
 * The driving time and distance will automatically set after clicking the button.
 *
 * @return
 *   The address form.
 */
class RoutePlannerAddressForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'route_planner_address_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    parent::submitForm($form, $form_state);
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['start'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Address'),
      '#default_value' => '',
      '#size'          => 20,
    );
    if (\Drupal::config('route_planner.settings')->get('route_planner_address_end')) {
      $form['end'] = array(
        '#type'          => 'textfield',
        '#title'         => $this->t('Target address'),
        '#default_value' => \Drupal::config('route_planner.settings')->get('route_planner_address'),
        '#size'          => 20,
      );
    }
    $form['distance'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Distance'),
      '#default_value' => '0.00',
      '#size'          => 20,
      '#disabled'      => TRUE,
    );

    $form['time'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Driving time'),
      '#default_value' => '0.00',
      '#size'          => 20,
      '#disabled'      => TRUE,
    );

    $form['button'] = array(
      '#type'       => 'button',
      '#value'      => $this->t('Calculate route'),
      '#attributes' => array('onClick' => 'return false;'),
    );

    return $form;
  }
}
