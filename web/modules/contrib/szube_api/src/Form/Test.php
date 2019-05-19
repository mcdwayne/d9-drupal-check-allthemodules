<?php

/**
 * @file
 * Contains \Drupal\demo\Form\DemoForm.
 */

namespace Drupal\szube_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\szube_api\SzuBeAPIHelper;


class Test extends FormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'szube_api_test';
  }


  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = SzuBeAPIHelper::getConfig();

    if ($id = $config->get('apiid')) {
      $form['test']['notes'] = [
        '#markup' => "<br>Your API id is : <b>$id</b>.<br><br>",
        '#value' => "Test now",
      ];

      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => "Test now",
      ];

    }
    else {
      $form['test']['notes'] = [
        '#markup' => '<br>You must configure API Key and API ID before tun the test.<br><br>',
        '#value' => "Test now",
      ];
    }


    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Run Test
    $test = new \Drupal\szube_api\SzuBeAPI\Test();
    $result = $test->test();

    if ($result) {
      drupal_set_message(json_encode($result, JSON_PRETTY_PRINT));
    }
    else {
      drupal_set_message("ERROR : Result empty.", 'error');

    }
  }

}
