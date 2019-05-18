<?php

namespace Drupal\accountkit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\accountkit\AccountKitManager;

/**
 * Class EmailLoginForm.
 */
class EmailLoginForm extends FormBase {

  /**
   * Drupal\accountkit\AccountKitManager definition.
   *
   * @var \Drupal\accountkit\AccountKitManager
   */
  protected $accountkitAccountkitManager;
  /**
   * Constructs a new EmailLoginForm object.
   */
  public function __construct(
    AccountKitManager $accountkit_accountkit_manager
  ) {
    $this->accountkitAccountkitManager = $accountkit_accountkit_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('accountkit.accountkit_manager')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'email_login_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#description' => $this->t('Please input your email address.'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#attributes' => ['id' => 'email-login-submit'],
    ];

    return $form + $this->accountkitAccountkitManager->getAdditionalFormDetails();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      drupal_set_message($key . ': ' . $value);
    }

  }

}
