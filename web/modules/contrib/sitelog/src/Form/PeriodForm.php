<?php

namespace Drupal\sitelog\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller.
 */
class PeriodForm extends FormBase {

  /**
   * Form builder.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['period'] = array(
      '#type' => 'radios',
      '#title' => t('Period'),
      '#options' => array(
        7 => t('Week to date'),
        30 => t('Month to date'),
        90 => t('Quarter to date'),
        365 => t('Year to date'),
        1095 => t('Three years to date'),
      ),
      '#default_value' => '90',
    );
    return $form;
  }

  /**
   * Form identifier getter method.
   */
  public function getFormId() {}

  /**
   * Submit handler.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}
}
