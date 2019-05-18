<?php

namespace Drupal\mailing_list\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\mailing_list\Entity\Subscription;
use Drupal\mailing_list\MailingListInterface;
use Drupal\mailing_list\SubscriptionInterface;
use Drupal\user\Entity\User;
use Egulias\EmailValidator\EmailValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mailing list import.
 */
class MailingListImportForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The mailing list to work with.
   *
   * @var \Drupal\mailing_list\MailingListInterface
   */
  protected $mailingList;

  /**
   * The email validator.
   *
   * @var \Egulias\EmailValidator\EmailValidator
   */
  protected $emailValidator;

  /**
   * Constructs a MailingListImportForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   *   The email validator.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation, EmailValidator $email_validator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
    $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation'),
      $container->get('email.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailing_list_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, MailingListInterface $mailing_list = NULL) {
    $this->mailingList = $mailing_list;
    $form['#title'] = $this->t('Import subscriptions into %label mailing list', ['%label' => $mailing_list->label()]);

    $form['emails'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email addresses'),
      '#description' => $this->t('The email addresses to subscribe, one per line.'),
      '#required' => TRUE,
      '#rows' => 10,
      '#resizable' => 'vertical',
      '#default_value' => '',
    ];

    $form['no_duplicate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prevent duplicates'),
      '#description' => $this->t('Do not create new subscriptions for already existent subscribed emails'),
      '#default_value' => TRUE,
    ];

    $form['activate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create as active'),
      '#description' => $this->t('Set active status for new subscriptions.'),
      '#default_value' => TRUE,
    ];

    $form['activate_existent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active existent'),
      '#description' => $this->t('Enable to activate existing inactive subscriptions.'),
      '#default_value' => FALSE,
      '#states' => [
        'disabled' => [
          ':input[name="activate"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['anonymous'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Anonymous subscriber'),
      '#description' => $this->t('New subscriptions will be owned by the anonymous user. Existing subscriptions will not be altered.'),
      '#default_value' => TRUE,
    ];

    $form['actions']['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    foreach (preg_split("/\\r\\n|\\r|\\n/", $form_state->getValue('emails')) as $email) {
      if (!empty($email) && !$this->emailValidator->isValid($email)) {
        $form_state->setErrorByName('emails', $this->t('%recipient is an invalid email address.', ['%recipient' => $email]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $storage = $this->entityTypeManager->getStorage('mailing_list_subscription');

    /** @var \Drupal\mailing_list\SubscriptionInterface[] $existent_subscriptions */
    $existent_subscriptions = [];
    if ($no_duplicate = $form_state->getValue('no_duplicate', TRUE)) {
      $query = $storage->getQuery()
        ->condition('mailing_list', $this->mailingList->id());

      /** @var \Drupal\mailing_list\SubscriptionInterface $subscription */
      foreach ($storage->loadMultiple($query->execute()) as $subscription) {
        $existent_subscriptions[$subscription->getEmail()] = $subscription;
      }
    }

    $activate = $form_state->getValue('activate', TRUE);
    $activate_existent = !$activate ? : $form_state->getValue('activate_existent', FALSE);
    $uid = $form_state->getValue('anonymous', TRUE) ? User::getAnonymousUser()->id() : $this->currentUser()->id();
    $count = 0;
    foreach (preg_split("/\\r\\n|\\r|\\n/", $form_state->getValue('emails')) as $email) {
      if (isset($existent_subscriptions[$email])) {
        $subscription = $existent_subscriptions[$email];
        if ($activate && $activate_existent && !$subscription->isActive()) {
          $subscription->setStatus(SubscriptionInterface::ACTIVE)->save();
          $count++;
        }
      }
      else {
        $subscription = Subscription::create([
          'title' => $email,
          'mailing_list' => $this->mailingList->id(),
          'email' => $email,
          'status' => $activate ? SubscriptionInterface::ACTIVE : SubscriptionInterface::INACTIVE,
          'uid' => $uid,
        ]);
        $existent_subscriptions[$email] = $subscription;
        $subscription->save();
        $count++;
      }
    }

    drupal_set_message(!$count
      ? $this->t('No subscriptions added.')
      : $this->formatPlural($count, '1 subscription added to %label mailing list.', '@count new subscriptions added to %label mailing list.', ['@count' => $count, '%label' => $this->mailingList->label()]));

    $form_state->setRedirect('entity.mailing_list.collection');
  }

}
