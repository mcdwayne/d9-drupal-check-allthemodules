<?php

namespace Drupal\form_options_attributes_test\Form;

class FormOptionsAttributesOptGroupTestForm extends \Drupal\Core\Form\FormBase {

  public function getFormId() {
    return 'form_options_attributes_module_optgroup_test_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form['states'] = [
      '#type' => 'select',
      '#title' => $this->t('States and Provinces'),
      '#options' => [
        $this->t('United States')->render() => [
          'AL' => $this->t('Alabama'),
          'AK' => $this->t('Alaska'),
          'AZ' => $this->t('Arizona'),
          'AR' => $this->t('Arkansas'),
          // ..
          'WI' => $this->t('Wisconsin'),
          'WY' => t('Wyoming'),
        ],
        $this->t('Canada')->render() => [
          'AB' => $this->t('Alberta'),
          'BC' => $this->t('British Columbia'),
          // ..
          'NU' => $this->t('Nunavut'),
          'YT' => $this->t('Yukon'),
        ],
      ],
      '#options_attributes' => [
        $this->t('United States')->render() => [
          'AL' => ['class' => ['southeast'], 'data-bbq-meat' => 'pork'],
          'AK' => ['class' => ['non-contiguous'], 'data-bbq-meat' => 'salmon'],
          'AZ' => ['class' => ['southwest'], 'data-bbq-meat' => 'rattlesnake'],
          'AR' => ['class' => ['south'], 'data-bbq-meat' => 'beef'],
          // ...
          'WI' => ['class' => ['midwest'], 'data-bbq-meat' => 'cheese'],
          'WY' => ['class' => ['flyover'], 'data-bbq-meat' => 'bison'],
        ],
        $this->t('Canada')->render() => [
          'AB' => ['class' => ['rocky'], 'data-bbq-meat' => 'beaver'],
          'BC' => ['class' => ['coastal'], 'data-bbq-meat' => 'otter'],
          // ..
          'NU' => ['class' => ['arctic'], 'data-bbq-meat' => 'walrus'],
          'YT' => ['class' => ['goldy'], 'data-bbq-meat' => 'moose'],
        ]
      ],
      '#attributes' => array('class' => array('states-bbq-selector')),
    ];
    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

  }


}
