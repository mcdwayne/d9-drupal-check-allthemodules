<?php
/**
 * @file
 * Contains \Drupal\solr_qb_Form\SolrQbCustomForm.
 */

namespace Drupal\solr_qb\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class SolrQbCustomForm extends FormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'solr_qb_custom_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['query'] = [
      '#title' => $this->t('Query'),
      '#type' => 'textarea',
      '#description' => $this->t('Example: @example (Do not use "wt" parameter. It will be provided automatically.)', ['@example' => 'q=*:*']),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Execute Query'),
    ];
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