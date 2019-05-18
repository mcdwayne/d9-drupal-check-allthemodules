<?php
namespace Drupal\mail_safety\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;
use Drupal\mail_safety\Controller\MailSafetyController;

/**
 * Class DeleteForm.
 *
 * @package Drupal\mail_safety\Form
 */
class DeleteForm extends ConfirmFormBase {

  /**
   * Store the mail safety array.
   *
   * @var array
   */
  protected $mailSafety = [];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mail_safety_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this mail?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('mail_safety.dashboard');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete mail');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $mail_safety = NULL) {
    $this->mailSafety = $mail_safety;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    MailSafetyController::delete($this->mailSafety['mail_id']);

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
