<?php

namespace Drupal\simple_styleguide\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class StyleguideExamples.
 *
 * @package Drupal\simple_styleguide\Form
 */
class StyleguideExamples extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'styleguide_examples';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['select_list'] = [
      '#type' => 'select',
      '#title' => $this->t('Select list'),
      '#description' => $this->t('This is an input description.'),
      '#options' => [
        'option one' => $this->t('option one'),
        'option 2' => $this->t('option 2'),
        'option 3' => $this->t('option 3'),
        'option 4' => $this->t('option 4'),
        'option 5' => $this->t('option 5'),
      ],
    ];
    $form['select_list_multi'] = [
      '#type' => 'select',
      '#title' => $this->t('Select list (multiple)'),
      '#description' => $this->t('This is an input description.'),
      '#multiple' => TRUE,
      '#options' => [
        'option one' => $this->t('option one'),
        'option 2' => $this->t('option 2'),
        'option 3' => $this->t('option 3'),
        'option 4' => $this->t('option 4'),
        'option 5' => $this->t('option 5'),
      ],
    ];
    $form['checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Single Checkbox'),
    ];
    $form['checkboxes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Checkboxes'),
      '#description' => $this->t('This is an input description.'),
      '#options' => [
        'option one' => $this->t('option one'),
        'option 2' => $this->t('option 2'),
        'option 3' => $this->t('option 3'),
        'option 4' => $this->t('option 4'),
        'option 5' => $this->t('option 5'),
      ],
    ];
    $form['radios'] = [
      '#type' => 'radios',
      '#title' => $this->t('Radios'),
      '#description' => $this->t('This is an input description.'),
      '#options' => [
        'option one' => $this->t('option one'),
        'option 2' => $this->t('option 2'),
        'option 3' => $this->t('option 3'),
        'option 4' => $this->t('option 4'),
        'option 5' => $this->t('option 5'),
      ],
    ];
    $form['textfield'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Textfield'),
      '#description' => $this->t('This is an input description.'),
      '#attributes' => [
        'placeholder' => 'Placeholder text',
      ],
      '#maxlength' => 64,
      '#size' => 64,
    ];
    $form['textarea'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Textarea'),
      '#description' => $this->t('This is an input description.'),
    ];
    $form['date'] = [
      '#type' => 'date',
      '#title' => $this->t('Date'),
      '#description' => $this->t('This is an input description.'),
    ];
    $form['file'] = [
      '#type' => 'file',
      '#title' => $this->t('File'),
      '#description' => $this->t('This is an input description.'),
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password w/out Confirmation'),
      '#description' => $this->t('This is an input description.'),
      '#maxlength' => 64,
      '#size' => 64,
    ];
    $form['password_confirm'] = [
      '#type' => 'password_confirm',
      '#title' => NULL,
      '#maxlength' => 64,
      '#size' => 64,
    ];
    $form['fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Fieldset'),
      '#description' => $this->t('This is a fieldset description.'),
    ];
    $form['fieldset']['markup'] = [
      '#markup' => 'Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec ullamcorper nulla non metus auctor fringilla. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Nullam id dolor id nibh ultricies vehicula ut id elit.',
    ];
    $form['fieldset']['textfield_two'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Textfield Inside Fieldset'),
      '#maxlength' => 64,
      '#size' => 64,
    ];
    $form['fieldset']['textfield_three'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Textfield Inside Fieldset'),
      '#description' => $this->t('This is an input description.'),
      '#maxlength' => 64,
      '#size' => 64,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $form['actions']['preview'] = [
      '#type' => 'button',
      '#value' => $this->t('Preview'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      drupal_set_message($key . ': ' . $value);
    }

  }

}
