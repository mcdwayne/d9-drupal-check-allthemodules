<?php

namespace Drupal\demo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Returns demo_manage_form where you can see reset dates and all.
 */
class DemoManageForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'demo_manage_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['status'] = [
      '#type' => 'container',
      '#title' => t('Status'),
      '#attributes' => [
        'class' => [
          'demo-status',
          'clearfix',
        ],
      ],
      '#attached' => [
        'library' => [
          'demo/demo-library',
        ],
      ],
    ];
    $reset_date = \Drupal::config('demo.settings')->get('demo_reset_last', 0);
    $form['status']['reset_last'] = [
      '#type' => 'item',
      '#title' => t('Last reset'),
      '#markup' => $reset_date ? format_date($reset_date) : t('Never'),
    ];

    $form['dump'] = demo_get_dumps();

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['delete'] = [
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#submit' => ['demo_manage_delete_submit'],
    ];

    // If there are no snapshots yet, hide the selection and form actions.
    if (empty($form['dump']['#options'])) {
      $form['dump']['#access'] = FALSE;
      $form['actions']['#access'] = FALSE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
