<?php

namespace Drupal\feedbacks\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Extends FormBase.
 *
 * @inheritdoc
 *
 * @group feedbacks
 */
class EditFeedback extends FormBase {

  protected $id;
  protected $feedback;
  protected $exists;

  /**
   * Setting Form Id.
   *
   * @inheritdoc
   */
  public function getFormId() {
    return 'feedbacks_edit';
  }

  /**
   * Building Form.
   *
   * @inheritdoc
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = '') {

    $this->id = $id;
    $this->exists = FeedbackClass::exists($this->id);

    if ($this->exists > 0) {
      $this->feedback = FeedbackClass::getOne($this->id);

      $url = Url::fromRoute('feedbacks_list.content')->toString();
      $delLink = Link::createFromRoute('Delete', 'feedbacks_delete.form',
        ['id' => $this->id])->toString();

      $form['path'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Site Page'),
        '#default_value' => $this->feedback['path'],
        '#required' => TRUE,
      ];

      $form['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#value' => $this->feedback['name'],
        '#disabled' => TRUE,
        '#required' => TRUE,
      ];

      $form['status'] = [
        '#type' => 'radios',
        '#options' => ['1' => $this->t('Open'), '2' => $this->t('Processed')],
        '#title' => $this->t('Status'),
        '#default_value' => $this->feedback['status'],
      ];

      $form['message'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Feedback'),
        '#default_value' => $this->feedback['message'],
        '#rows' => 3,
        '#required' => TRUE,
      ];

      $form['actions'] = [
        '#type' => 'actions',
      ];

      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
      ];

      $form['actions']['cancel'] = [
        '#type' => 'button',
        '#value' => $this->t('Cancel'),
        '#attributes' => ['onClick' => 'location.href = "' . $url . '"; event.preventDefault();'],
      ];
      $form['actions']['delete'] = [
        '#type' => 'markup',
        '#markup' => $delLink,
      ];

      return $form;
    }
    else {
      throw new NotFoundHttpException();
    }
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
   * Submit Form.
   *
   * @inheritdoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handle submitted form data.
    $redirectUrl = Url::fromRoute('feedbacks_list.content');
    $values = $form_state->getValues();
    $status = $values['status'];
    $message = $values['message'];
    $path = $values['path'];
    $updated = FeedbackClass::update($status, $message, $path, $this->id);
    if ($updated > 0) {
      $form_state->setRedirectUrl($redirectUrl);
      drupal_set_message($this->t('Feedback has been updated'));
    }
    else {
      drupal_set_message($this->t('Feedback not updated', 'error'));
    }
  }

}
