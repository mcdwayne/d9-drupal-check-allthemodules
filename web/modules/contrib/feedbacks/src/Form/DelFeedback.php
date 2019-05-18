<?php

namespace Drupal\feedbacks\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Extends ConfirmFormBase.
 *
 * @inheritdoc
 */
class DelFeedback extends ConfirmFormBase {
  protected $id;

  /**
   * Setting FormID.
   *
   * @inheritdoc
   */
  public function getFormId() {
    return 'feedbacks_delete';
  }

  /**
   * Setting Question text.
   *
   * @inheritdoc
   */
  public function getQuestion() {
    return t('Are you sure you want to delete submission %id?', ['%id' => $this->id]);
  }

  /**
   * Setting Confirm Text.
   *
   * @inheritdoc
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * Setting Cancel Url.
   *
   * @inheritdoc
   */
  public function getCancelUrl() {
    return new Url('feedbacks_list.content');
  }

  /**
   * Building Delete Form.
   *
   * @inheritdoc
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = '') {
    $this->id = $id;
    $this->exists = FeedbackClass::exists($this->id);

    if ($this->exists > 0) {
      return parent::buildForm($form, $form_state);
    }
    else {
      throw new NotFoundHttpException();
    }
  }

  /**
   * Submit Form.
   *
   * @inheritdoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $url = Url::fromRoute('feedbacks_list.content');
    FeedbackClass::delete($this->id);
    drupal_set_message(t('Feedback %id has been deleted.', ['%id' => $this->id]));
    $form_state->setRedirectUrl($url);
  }

}
