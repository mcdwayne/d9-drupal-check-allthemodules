<?php

namespace Drupal\mailing_list\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\mailing_list\SubscriptionInterface;
use Drupal\Core\Mail\MailManagerInterface;

/**
 * Builds the form to request access to own subscriptions for anonymous users.
 */
class AnonymousSubscriptionAccessForm extends FormBase {

  /**
   * The mailing list subscription entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $subscriptionStorage;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Construct a new AnonymousSubscriptionAccessForm object.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $subscription_storage
   *   The mailing list subscription entity storage.
   */
  public function __construct(EntityStorageInterface $subscription_storage, MailManagerInterface $mail_manager) {
    $this->subscriptionStorage = $subscription_storage;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('mailing_list_subscription'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailing_list_anonymous_subscription_access_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [
      '#title' => $this->t('Manage your subscriptions'),
      'indications' => [
        '#type' => '#markup',
        '#markup' => $this->t('Enter your email address to receive a message with a link to manage your subscriptions.'),
      ],
      'email' => [
        '#type' => 'email',
        '#title' => $this->t('Your email'),
        '#required' => TRUE,
      ],
      'actions' => [
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Send'),
          '#submit' => ['::submitForm'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // No validations needed.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Search for at least one active subscription for the given email address.
    $email = $form_state->getValue('email');
    $subscription_result = $this->subscriptionStorage->getQuery()
      ->condition('status', SubscriptionInterface::ACTIVE)
      ->condition('email', $email)
      ->condition('uid', 0)
      ->range(0, 1)
      ->execute();

    if (count($subscription_result)) {
      /** @var \Drupal\mailing_list\SubscriptionInterface $subscription */
      $subscription = $this->subscriptionStorage->load(array_pop($subscription_result));
      $manage_url = Url::fromRoute('mailing_list.access_subscription', [
        'sid' => $subscription->id(),
        'hash' => $subscription->getAccessHash(),
        'rel' => 'manage',
      ]);

      if ($manage_url->access()) {
        $this->mailManager->mail('mailing_list', 'anonymous_subscription_access', $email, $subscription->language(), ['manage_url' => $manage_url->setAbsolute()->toString()]);
      }
    }

    // Returns the same message to prevent subscribers email disclosure.
    drupal_set_message($this->t('Your request has been successfully processed. You will receive a message with access instructions only if at least one active subscription was found for your email. If you do not receive any messages in short, you probably do not have any active subscription on this site.'));
    $form_state->setRedirectUrl(Url::fromRoute('<front>'));
  }

}
