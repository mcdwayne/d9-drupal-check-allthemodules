<?php

namespace Drupal\uc_report\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Generates yearly sales report.
 */
class YearlySalesReport extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $year) {
    $form['year'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sales year'),
      '#default_value' => $year,
      '#maxlength' => 4,
      '#size' => 4,
      '#prefix' => '<div class="sales-year">',
      '#suffix' => '</div>',
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('View'),
      '#prefix' => '<div class="sales-year">',
      '#suffix' => '</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('uc_report.yearly.sales', ['year' => $form_state->getValue('year')]);
  }

}
