<?php

/**
 * @file
 * Contains \Drupal\form_protect_test\Form\TestForm.
 */

namespace Drupal\form_protect_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class TestForm extends FormBase {

  /**
   * Form instance.
   *
   * @var string
   */
  protected $instance;

  public function __construct($instance) {
    $this->instance = (string) $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "form_protect_test_form{$this->instance}";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form["text{$this->instance}"] = [
      '#type' => 'textfield',
      '#title' => 'Foo',
    ];
    $form["submit{$this->instance}"] = [
      '#type' => 'submit',
      '#value' => "Bar{$this->instance}",
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) { }

}
