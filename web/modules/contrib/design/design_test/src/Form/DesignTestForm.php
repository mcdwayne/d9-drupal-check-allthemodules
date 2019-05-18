<?php

/**
 * @file
 * Contains \Drupal\design_test\Form\DesignTestForm.
 */

namespace Drupal\design_test\Form;

use Drupal\Core\Form\FormBase;

/**
 * Form test dispatcher.
 */
class DesignTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'design_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $test = NULL) {
    $path = drupal_get_path('module', 'design_test');
    include_once DRUPAL_ROOT . "/$path/form/$test.inc";

    $test = strtr($test, array('-' => '_'));
    $function = 'design_test_form_' . $test;
    $form = $function($form, $form_state);

    $test = strtr($test, array('_' => ' '));
    $form['#title'] = ucfirst($test);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  function submitForm(array &$form, array &$form_state) {
  }

}
