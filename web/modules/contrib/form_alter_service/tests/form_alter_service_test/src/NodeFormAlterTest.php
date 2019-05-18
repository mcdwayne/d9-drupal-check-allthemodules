<?php

namespace Drupal\form_alter_service_test;

use Drupal\Core\Form\FormStateInterface;
use Drupal\form_alter_service\FormAlterBase;

/**
 * {@inheritdoc}
 */
class NodeFormAlterTest extends FormAlterBase {

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state) {
    list(, $minor) = explode('.', \Drupal::VERSION, 3);

    $markup = [];

    foreach ($form_state->getTemporary() as $key => $value) {
      $markup[] = "$key:$value";
    }

    $form['markup'] = [
      '#markup' => implode('|', $markup),
    ];

    /* @see \Drupal\Tests\form_alter_service\Functional\BrowserTest::testHandlersPrioritisation() */
    $form['actions'][$minor < 4 ? 'publish' : 'submit']['#submit'][] = [$this, 'submitButton'];
  }

  /**
   * {@inheritdoc}
   *
   * @FormValidate(
   *   priority = 0,
   *   strategy = "prepend",
   * )
   */
  public function validateSecond(array &$form, FormStateInterface $form_state) {
    $form_state->setTemporaryValue('validate2', __FUNCTION__);
  }

  /**
   * {@inheritdoc}
   *
   * @FormValidate(
   *   priority = -5,
   *   strategy = "prepend",
   * )
   */
  public function validateThird(array &$form, FormStateInterface $form_state) {
    $form_state->setTemporaryValue('validate3', __FUNCTION__);
  }

  /**
   * {@inheritdoc}
   *
   * @FormValidate(
   *   priority = 10,
   *   strategy = "prepend",
   * )
   */
  public function validateFirst(array &$form, FormStateInterface $form_state) {
    $form_state->setTemporaryValue('validate1', __FUNCTION__);
  }

  /**
   * {@inheritdoc}
   *
   * @FormSubmit(
   *   strategy = "prepend",
   * )
   */
  public function submitTest(array $form, FormStateInterface $form_state) {
    // This method will be skipped because the form has a submit button with
    // the "#submit" property.
    /* @see \Drupal\Core\Form\FormBuilder::doBuildForm() */
    $form_state->setTemporaryValue('submitSkip1', __FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function submitButton(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

}
