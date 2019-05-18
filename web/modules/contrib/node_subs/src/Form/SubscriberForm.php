<?php

namespace Drupal\node_subs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Egulias\EmailValidator\EmailValidator;
use Drupal\node_subs\Service\AccountService;

/**
 * Class SubscriberForm.
 */
class SubscriberForm extends FormBase {

  private $subscriber;

  private $emailValidator;

  /**
   * @var \Drupal\node_subs\Service\AccountService
   */
  private $account;

  /**
   * {@inheritdoc}
   */
  public function __construct(EmailValidator $email_validator, AccountService $account_service) {
    $this->emailValidator = $email_validator;
    $this->account = $account_service;
    $subscriber_id = \Drupal::routeMatch()->getParameter('subscriber_id');
    $this->subscriber = $subscriber_id ? $this->account->load($subscriber_id) : NULL;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('email.validator'),
      $container->get('node_subs.account')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_subs_subscriber_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $subscriber_id = NULL) {
    $subscriber = $this->subscriber;
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $subscriber ? $subscriber->name : '',
    ];
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('E-mail'),
      '#default_value' => $subscriber ? $subscriber->email : '',
    ];
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Status'),
      '#default_value' => $subscriber ? $subscriber->status : '',
    ];
    if ($this->subscriber) {
      $form['id'] = array(
        '#type' => 'value',
        '#value' => $this->subscriber->id,
      );
    }
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if (!empty($form_state->getValue('email'))) {
      $email = $form_state->getValue('email');
      if (!$this->emailValidator->isValid($email)) {
        $form_state->setErrorByName('email', $this->t('You must enter a valid e-mail address.'));
      }
      else {
        $exists_account = $this->account->loadByEmail($email, ['deleted' => 0]);
        if (empty($form['id']) && $exists_account) {
          $form_state->setErrorByName('email', $this->t('The e-mail address %email is already taken.', array('%email' => $email)));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $subscriber = (object) $form_state->getValues();

    if (isset($subscriber->id)) {
      $message = t('Subscriber @name (@email) updated', array(
        '@name' => $subscriber->name,
        '@email' => $subscriber->email
      ));
    }
    else {
      $message = t('Subscriber @name (@email) created', array(
        '@name' => $subscriber->name,
        '@email' => $subscriber->email
      ));
    }
    $this->account->save($subscriber, FALSE);
    drupal_set_message($message);
    $form_state->setRedirect('node_subs.subscribers');
  }

  public function getPageTitle() {
    return '';
  }

}
