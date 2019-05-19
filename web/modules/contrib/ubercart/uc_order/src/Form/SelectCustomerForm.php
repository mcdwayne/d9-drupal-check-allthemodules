<?php

namespace Drupal\uc_order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to choose a customer from a list.
 */
class SelectCustomerForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_order_select_customer_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $operation = NULL, $options = NULL) {
    if ($operation == '' && is_null($options)) {
      $form['desc'] = [
        '#type' => 'container',
        '#markup' => $this->t('Search for a customer based on these fields.') . '<br />' .
                     $this->t('Use * as a wildcard to match any character.') . '<br />' .
                     '(<em>' . $this->t('Leave a field empty to ignore it in the search.') . '</em>)',
      ];

      $form['first_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('First name'),
        '#size' => 24,
        '#maxlength' => 32,
      ];

      $form['last_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Last name'),
        '#size' => 24,
        '#maxlength' => 32,
      ];

      $form['email'] = [
        '#type' => 'email',
        '#title' => $this->t('E-mail'),
        '#size' => 24,
        '#maxlength' => 96,
      ];
    }
    elseif ($operation == 'search' && !is_null($options)) {
      $form['cust_select'] = [
        '#type' => 'select',
        '#title' => $this->t('Select a customer'),
        '#size' => 7,
        '#options' => $options,
        '#default_value' => key($options),
        '#attributes' => ['ondblclick' => 'return select_customer_search();'],
      ];
    }
    elseif ($operation == 'new') {
      $form['desc'] = [
        '#type' => 'container',
        '#markup' => $this->t('Enter an e-mail address for the new customer.'),
      ];

      $form['email'] = [
        '#type' => 'email',
        '#title' => $this->t('E-mail'),
        '#size' => 24,
        '#maxlength' => 96,
        '#required' => TRUE,
      ];
    }

    $form['actions'] = ['#type' => 'actions'];
    if (is_null($operation)) {
      $form['actions']['search'] = [
        '#type' => 'submit',
        '#value' => $this->t('Search'),
        '#attributes' => ['id' => 'load-customer-search-results'],
      ];
    }
    elseif ($operation == 'search') {
      if (!is_null($options)) {
        $form['actions']['select'] = [
          '#type' => 'submit',
          '#value' => $this->t('Select'),
          '#attributes' => ['id' => 'select-customer-search'],
        ];
      }
      $form['actions']['back'] = [
        '#type' => 'submit',
        '#value' => $this->t('Back'),
        '#attributes' => ['id' => 'load-customer-search'],
      ];
    }
    elseif ($operation == 'new') {
      $form['sendmail'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('E-mail customer account details.'),
      ];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
        '#attributes' => ['id' => 'check-new-customer-address'],
      ];
    }

    $form['actions']['close'] = [
      '#type' => 'submit',
      '#value' => $this->t('Close'),
      '#attributes' => ['id' => 'close-customer-select'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
