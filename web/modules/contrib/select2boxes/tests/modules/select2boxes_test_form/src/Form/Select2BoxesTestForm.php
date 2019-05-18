<?php

namespace Drupal\select2boxes_test_form\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Select2BoxesTestForm.
 *
 * @package Drupal\select2boxes_test_form\Form
 */
class Select2BoxesTestForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'select2boxes_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    return [
      '#type'           => 'select',
      '#options'        => $this->generateRandomOptions(),
      '#empty_value'    => 'none',
      '#empty_option'   => '- Select Value -',
      '#attributes'     => [
        'data-jquery-once-autocomplete'         => 'true',
        'data-select2-autocomplete-list-widget' => 'true',
        'class'                                 => ['select2-widget'],
      ],
    ];
  }

  /**
   * Generates random set of options.
   */
  protected function generateRandomOptions() {
    $options = [];
    for ($i = 0; $i < 9; $i++) {
      $options[$i] = mt_rand(0, 255);
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty submit method.
  }

}
