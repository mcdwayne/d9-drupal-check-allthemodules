<?php

namespace Drupal\braintree_cashier\Form;

use Drupal\braintree_cashier\BillableUser;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\braintree_api\BraintreeApiService;
use Drupal\Core\Logger\LoggerChannel;

/**
 * Class PaymentMethodForm.
 */
class PaymentMethodForm extends FormBase {

  /**
   * Drupal\braintree_api\BraintreeApiService definition.
   *
   * @var \Drupal\braintree_api\BraintreeApiService
   */
  protected $braintreeApiBraintreeApi;

  /**
   * Drupal\Core\Logger\LoggerChannel definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * The billable user service.
   *
   * @var \Drupal\braintree_cashier\BillableUser
   */
  protected $billableUser;

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a new PaymentMethodForm object.
   */
  public function __construct(BraintreeApiService $braintree_api_braintree_api, LoggerChannel $logger_channel_braintree_cashier, EntityTypeManagerInterface $entity_type_manager, BillableUser $billable_user) {
    $this->braintreeApiBraintreeApi = $braintree_api_braintree_api;
    $this->logger = $logger_channel_braintree_cashier;
    $this->billableUser = $billable_user;
    $this->userStorage = $entity_type_manager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('braintree_api.braintree_api'),
      $container->get('logger.channel.braintree_cashier'),
      $container->get('entity_type.manager'),
      $container->get('braintree_cashier.billable_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'payment_method_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, User $user = NULL) {

    $form['#attributes']['id'] = 'payment-method-form';

    // The form submit handler isn't triggered by jQuery's $().submit().
    // This hidden button is added for jQuery to click on to submit the form.
    $form['final_submit'] = [
      '#type' => 'submit',
      '#name' => 'final_submit',
      '#attributes' => [
        'id' => 'final-submit',
        'class' => [
          'visually-hidden',
        ],
      ],
      '#submit' => [[$this, 'submitForm']],
    ];

    $form['uid'] = [
      '#type' => 'value',
      '#value' => $user->id(),
    ];

    $form['dropin_container'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'dropin-container',
      ],
    ];

    $form['#attached']['library'][] = 'braintree_cashier/dropin_support';
    $form['#attached']['drupalSettings']['braintree_cashier'] = [
      'authorization' => $this->billableUser->generateClientToken($user),
      'acceptPaypal' => (bool) $this->config('braintree_cashier.settings')->get('accept_paypal'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#button_type' => 'primary',
      'submit' => [
        '#type' => 'submit',
        '#disabled' => TRUE,
        '#attributes' => [
          'id' => 'submit-button',
          'class' => [
            'btn-success',
          ],
        ],
      ],
    ];

    if ($this->billableUser->getBraintreeCustomerId($user) && !empty($this->billableUser->getPaymentMethod($user))) {
      $form['actions']['submit']['#value'] = $this->t('Replace payment method');
    }
    else {
      $form['actions']['submit']['#value'] = $this->t('Add payment method');
    }

    $form['payment_method_nonce'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => 'payment-method-nonce',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $values = $form_state->getValues();
    if (empty($values['payment_method_nonce'])) {
      $message = $this->t('The payment method could not be updated.');
      $form_state->setErrorByName('payment_method_nonce', $message);
      $this->logger->error($message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    /** @var \Drupal\user\Entity\User $user */
    $user = $this->userStorage->load($values['uid']);
    if (empty($this->billableUser->getBraintreeCustomerId($user))) {
      $result = $this->billableUser->createAsBraintreeCustomer($user, $values['payment_method_nonce']);
    }
    else {
      $result = $this->billableUser->updatePaymentMethod($user, $values['payment_method_nonce']);
    }
    if ($result) {
      $this->messenger()->addStatus($this->t('Your payment method has been updated successfully!'));
    }
    else {
      $this->messenger()->addError($this->t('There was an error updating your payment method. Please try again.'));
    }
  }

  /**
   * Access control handler for this route.
   */
  public function accessRoute(AccountInterface $browsing_account, User $user = NULL) {
    $is_allowed = $browsing_account->isAuthenticated() && !empty($user) && ($browsing_account->id() == $user->id() || $browsing_account->hasPermission('administer braintree cashier'));
    return AccessResultAllowed::allowedIf($is_allowed);
  }

}
