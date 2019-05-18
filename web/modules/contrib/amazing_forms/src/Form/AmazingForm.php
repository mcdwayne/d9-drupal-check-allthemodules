<?php

namespace Drupal\amazing_forms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * AmazingForm class.
 */
class AmazingForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'amazing_forms_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $options = NULL) {
    $form['#prefix'] = '<div id="amazing_form_example">';
    $form['#suffix'] = '</div>';

    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => 'Sunil Kumar',
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#attributes' => [
        'class' => ['row', 'expanded'],
        'id' => ['login-page-block'],
        'placeholder' => $this->t('sunil.kumar@gmail.com'),
      ],
      '#required' => TRUE,
    ];
    $form['dob'] = [
      '#type' => 'date',
      '#title' => $this->t('DOB'),
      '#required' => TRUE,
    ];

    $options = ['_none' => $this->t('Select Continent'), 'africa' => $this->t('Africa'), 'antarctica' => $this->t('Antarctica'), 'asia' => $this->t('Asia'), 'australia' => $this->t('Australia/Oceania'), 'europe' => $this->t('Europe'), 'north_america' => $this->t('North America'), 'south_america' => $this->t('South America'),];

    $form['continent'] = [
      '#type' => 'select',
      '#title' => $this->t('Continent'),
      '#options' => $options,
    ];

    $form['gender'] = [
      '#type' => 'radios',
      '#title' => $this->t('Gender'),
      '#options' => [
        'Female' => $this->t('Female'),
        'male' => $this->t('Male'),
      ],
    ];

    $form['our_checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I Agree: to this form is useful!'),
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#attributes' => [
        'class' => [
          'use-ajax',
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'submitModalFormAjax'],
        'event' => 'click',
      ],
    ];

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $form;
  }

  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // If there are any form errors, re-display the form.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#amazing_form_example', $form));
    }
    else {
      $response->addCommand(new OpenModalDialogCommand("Request sent!", 'Your request has been submitted, contact you soon...', ['width' => 800]));
    }
    return $response;
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
    return $data;
  }

}
