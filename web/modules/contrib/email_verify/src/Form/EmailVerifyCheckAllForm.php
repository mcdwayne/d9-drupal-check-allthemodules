<?php

/**
 * @file
 * Contains \Drupal\email_verify\Form\EmailVerifyCheckAllForm.
 */

namespace Drupal\email_verify\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\email_verify\EmailVerifyManager;
use Drupal\email_verify\EmailVerifyBatch;

class EmailVerifyCheckAllForm extends ConfirmFormBase {

  /**
   * An Email Verify manager instance.
   *
   * @var \Drupal\email_verify\EmailVerifyManager
   */
  protected $manager;

  /**
   * Constructs a new EmailVerifyCheckAllForm.
   *
   * @param \Drupal\email_verify\EmailVerifyManagerInterface $manager
   *   A Email Verify manager instance.
   */
  public function __construct(EmailVerifyManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('email_verify.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'email_verify_check_all';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to check all user email addresses?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.user.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Check email addresses');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('This action verifies all user email addresses and may be a lengthy
      process.');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->manager->checkAll();
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
