<?php

namespace Drupal\simple_a_b\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Create a delete form.
 */
class SimpleABDeleteForm extends ConfirmFormBase {

  protected $tid;

  protected $loadedData;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_a_b_test_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $message = t('You cannot delete, which you cannot find...');

    if (!empty($this->loadedData)) {
      $message = t('Are you sure you want to delete "@name" test?', ['@name' => $this->loadedData['name']]);
    }

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('simple_a_b.view_tests');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Please make sure this is the test you want to delete. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $tid = NULL) {
    $this->tid = $tid;

    $this->loadedData = $this->loadData($this->tid);

    if (empty($this->loadedData)) {
      $form = [];
      // Set error message.
      drupal_set_message(t('Error the test could not be found'), 'error');

      return $form;
    }
    else {
      return parent::buildForm($form, $form_state);
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Try to update the existing test.
    $remove = \Drupal::service('simple_a_b.storage.test')->remove($this->tid);

    if ($remove != TRUE) {
      drupal_set_message(t('Error deleting test'), 'error');
    }
    else {
      // Otherwise display message.
      drupal_set_message(t('"@name" has been removed', ['@name' => $this->loadedData['name']]), 'status');

      // Redirect back to viewing all tests.
      $url = Url::fromRoute('simple_a_b.view_tests');
      $form_state->setRedirectUrl($url);
    }
  }

  /**
   * Load a tests information used for amending edits.
   *
   * @param int $tid
   *   Optional tid int to get data.
   *
   * @return array
   *   Returns an empty or data full array
   */
  protected function loadData($tid = -1) {
    $output = [];

    // If there is no tid, then simply return empty array.
    if ($tid === -1) {
      return $output;
    }

    // Otherwise run a fetch looking up the test id.
    $tests = \Drupal::service('simple_a_b.storage.test')->fetch($tid);

    // If we find any tests,
    // set it to the output after converting it to an array.
    if (count($tests) > 0) {
      foreach ($tests as $test) {
        // There should only be one found.
        $output = (array) $test;
      }
    }

    // Return the array.
    return $output;
  }

}
