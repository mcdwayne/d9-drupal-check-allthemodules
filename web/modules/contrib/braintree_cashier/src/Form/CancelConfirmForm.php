<?php

namespace Drupal\braintree_cashier\Form;

use Drupal\braintree_cashier\BillableUser;
use Drupal\braintree_cashier\Event\BraintreeCashierEvents;
use Drupal\braintree_cashier\Event\SubscriptionCanceledByUserEvent;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\braintree_cashier\SubscriptionService;
use Drupal\Core\Logger\LoggerChannel;

/**
 * Class CancelConfirmForm.
 */
class CancelConfirmForm extends ConfirmFormBase {

  /**
   * Drupal\braintree_cashier\SubscriptionService definition.
   *
   * @var \Drupal\braintree_cashier\SubscriptionService
   */
  protected $subscriptionService;
  /**
   * Drupal\Core\Logger\LoggerChannel definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $loggerChannelBraintreeCashier;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The billable user service.
   *
   * @var \Drupal\braintree_cashier\BillableUser
   */
  protected $billableUser;

  /**
   * The event dispatcher.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * Constructs a new CancelConfirmForm object.
   *
   * @param \Drupal\braintree_cashier\SubscriptionService $braintree_cashier_subscription_service
   *   The subscription service.
   * @param \Drupal\Core\Logger\LoggerChannel $logger_channel_braintree_cashier
   *   The braintree_cashier logger channel.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\braintree_cashier\BillableUser $billableUser
   *   The billable user service.
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $eventDispatcher
   *   The event dispatcher.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(SubscriptionService $braintree_cashier_subscription_service, LoggerChannel $logger_channel_braintree_cashier, EntityTypeManagerInterface $entityTypeManager, BillableUser $billableUser, ContainerAwareEventDispatcher $eventDispatcher) {
    $this->subscriptionService = $braintree_cashier_subscription_service;
    $this->loggerChannelBraintreeCashier = $logger_channel_braintree_cashier;
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->billableUser = $billableUser;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('braintree_cashier.subscription_service'),
      $container->get('logger.channel.braintree_cashier'),
      $container->get('entity_type.manager'),
      $container->get('braintree_cashier.billable_user'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to cancel your subscription?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Yes, I wish to cancel.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return t("No, I don't want to cancel.");
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'subscription_cancel_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, User $user = NULL) {
    $form['uid'] = [
      '#type' => 'value',
      '#value' => $user->id(),
    ];

    $form['reason'] = [
      '#type' => 'textarea',
      '#title' => t('Any special reason?'),
      '#description' => t("You can skip this section if you want, but if there's a specific reason behind your cancellation, a quick explanation would be greatly appreciated. We're always trying to improve the site!"),
    ];

    $form = parent::buildForm($form, $form_state);

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    /** @var \Drupal\user\Entity\User $user */
    $user = $this->userStorage->load($values['uid']);
    $subscriptions = $this->billableUser->getSubscriptions($user);
    /** @var \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface $subscription */
    foreach ($subscriptions as $subscription) {
      if (!empty($values['reason'])) {
        $subscription->setCancelMessage($values['reason']);
        $subscription->save();
      }
      /** @var \Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface $subscription */
      $this->subscriptionService->cancel($subscription);
      // Set the canceled at date field.
      $subscription->setCanceledAtDate(time());
      $subscription->save();
      $event = new SubscriptionCanceledByUserEvent($subscription);
      $this->eventDispatcher->dispatch(BraintreeCashierEvents::SUBSCRIPTION_CANCELED_BY_USER, $event);
    }
    $form_state->setRedirect('braintree_cashier.my_subscription', [
      'user' => $values['uid'],
    ]);
    $this->messenger()->addStatus(t('Billing for your subscription has been canceled.'));
  }

}
