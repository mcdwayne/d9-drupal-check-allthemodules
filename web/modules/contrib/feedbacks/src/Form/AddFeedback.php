<?php

namespace Drupal\feedbacks\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extends FormBase.
 *
 * @inheritdoc
 *
 * @group feedbacks
 */
class AddFeedback extends FormBase {

  /**
   * Setting formId.
   *
   * @inheritdoc
   */
  public function getFormId() {
    return 'feedbacks_form';
  }

  /**
   * Building Form.
   *
   * @inheritdoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['name'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Name'),
      '#value' => $this->currentUser()->getAccountName(),
      '#required' => TRUE,
    ];

    $form['path'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Feedback Page'),
      '#value' => Request::createFromGlobals()->server->get('HTTP_REFERER'),
      '#required' => TRUE,
    ];

    $form['feedback_information'] = [
      '#type' => 'markup',
      '#markup' => 'If you experience a bug or would like to see an addition on the current page,
      feel free to leave us a message.',
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Feedback'),
      '#rows' => 3,
      '#title_display' => 'invisible',
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Feedback'),
    ];

    return $form;
  }

  /**
   * Form Validation.
   *
   * @inheritdoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate submitted form data.
  }

  /**
   * Form Submission.
   *
   * @inheritdoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $url = Url::fromUri(Request::createFromGlobals()->server->get('HTTP_REFERER'));
    $values = $form_state->getValues();
    $name = $values['name'];
    $message = $values['message'];
    $path = $values['path'];
    $timestamp = time();
    FeedbackClass::add($name, $message, $path, $timestamp);
    $form_state->setRedirectUrl($url);
    drupal_set_message($this->t('Your Feedback has been submitted'));
  }

}
