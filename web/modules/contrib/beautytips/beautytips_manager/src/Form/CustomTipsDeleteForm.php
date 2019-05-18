<?php

namespace Drupal\beautytips_manager\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Url;

class CustomTipsDeleteForm extends ConfirmFormBase {

  protected $tip;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'beautytips_manager_delete_tip_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to the beautytip applied to element %element?', ['%element' => $this->tip->element]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('beautytips_manager.customTips');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $this->tip = beautytips_manager_get_custom_tip($id);

    if (empty($this->tip)) {
      throw new NotFoundHttpException();
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    beautytips_manager_delete_custom_tip($this->tip->id);
    \Drupal::cache()->delete('beautytips:beautytips-ui-custom-tips');
    $form_state->setRedirect('beautytips_manager.customTips');
  }
}
