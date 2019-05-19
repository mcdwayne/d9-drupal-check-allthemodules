<?php

/**
 * @file
 * Contains \Drupal\testswarm\Form\TestswarmClearTestDetailsForm.
 */

namespace Drupal\testswarm\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\testswarm\TestswarmStorageController;

/**
 * Defines the testswarm clear test details form.
 */
class TestswarmClearTestDetailsForm extends ConfirmFormBase {

  protected $test;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'testswarm_clear_test_details_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if ($this->test == 'all') {
      return t('Are you sure you want to remove all test details?');
    }
    else {
      return t('Are you sure you want to remove all test details of %test?', array('%test' => $this->test));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'testswarm_tests'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $test = '') {
    $this->test = $test;

    return parent::buildForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  function submitForm(array &$form, array &$form_state) {
    $config = config('testswarm.settings');
    TestswarmStorageController::deleteTest($this->test);
    if ($config->get('testswarm_save_results_remote', 0) && module_exists('xmlrpc')) {
      $result = xmlrpc(
        $config->get('testswarm_save_results_remote_url'),
        array(
          'testswarm.test.delete' => array(
            $this->test,
            REQUEST_TIME,
            testswarm_xmlrpc_get_hash(),
          ),
        )
      );
      if (!$result) {
        $error = xmlrpc_error();
        if ($error) {
          watchdog('testswarm_xmlrpc', $error->code . ': ' . check_plain($error->message));
        }
        else {
          watchdog('testswarm_xmlrpc', 'Something went wrong deleting the result from the remote server');
        }
      }
    }
    $form_state['redirect'] = 'testswarm-tests';
  }
}
