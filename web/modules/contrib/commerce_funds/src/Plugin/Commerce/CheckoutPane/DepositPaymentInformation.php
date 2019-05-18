<?php

namespace Drupal\commerce_funds\Plugin\Commerce\CheckoutPane;

use Drupal\commerce\InlineFormManager;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_payment\PaymentOption;
use Drupal\commerce_payment\PaymentOptionsBuilderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface;
use Drupal\commerce_payment\Plugin\Commerce\CheckoutPane\PaymentInformation;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the payment information pane.
 *
 * This class is not a plugin as we need to use PaymentInformation
 * to get the checkout flow working. A hook is implemented to override
 * PaymentInformation.
 *
 * @see Drupal\commerce_payment\Plugin\Commerce\CheckoutPane\PaymentInformation
 * @see commerce_funds_commerce_checkout_pane_info_alter()
 */
class DepositPaymentInformation extends PaymentInformation {

  /**
   * The account interface.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The inline form manager.
   *
   * @var \Drupal\commerce\InlineFormManager
   */
  protected $inlineFormManager;

  /**
   * The payment options builder.
   *
   * @var \Drupal\commerce_payment\PaymentOptionsBuilderInterface
   */
  protected $paymentOptionsBuilder;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new PaymentInformation object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow
   *   The parent checkout flow.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\commerce\InlineFormManager $inline_form_manager
   *   The inline form manager.
   * @param \Drupal\commerce_payment\PaymentOptionsBuilderInterface $payment_options_builder
   *   The payment options builder.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, InlineFormManager $inline_form_manager, PaymentOptionsBuilderInterface $payment_options_builder, MessengerInterface $messenger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow, $entity_type_manager, $current_user, $inline_form_manager, $payment_options_builder);
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $checkout_flow,
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('plugin.manager.commerce_inline_form'),
      $container->get('commerce_payment.options_builder'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    // Return parent pane if not a deposit.
    if ($this->order->get('type')->getValue()[0]['target_id'] !== 'deposit') {
      return parent::buildPaneForm($pane_form, $form_state, $complete_form);
    }
    if ($this->order->isPaid() || $this->order->getTotalPrice()->isZero()) {
      // No payment is needed if the order is free or has already been paid.
      // In that case, collect just the billing information.
      $pane_form['#title'] = $this->t('Billing information');
      $pane_form = parent::buildBillingProfileForm($pane_form, $form_state);
      return $pane_form;
    }

    /** @var \Drupal\commerce_payment\PaymentGatewayStorageInterface $payment_gateway_storage */
    $payment_gateway_storage = $this->entityTypeManager->getStorage('commerce_payment_gateway');
    // Load the payment gateways. This fires an event for filtering the
    // available gateways, and then evaluates conditions on all remaining ones.
    $payment_gateways = $payment_gateway_storage->loadMultipleForOrder($this->order);
    // Can't proceed without any payment gateways.
    if (empty($payment_gateways)) {
      $this->messenger->addError($this->noPaymentGatewayErrorMessage());
      return $pane_form;
    }

    // Prepare the form for ajax.
    $pane_form['#wrapper_id'] = Html::getUniqueId('payment-information-wrapper');
    $pane_form['#prefix'] = '<div id="' . $pane_form['#wrapper_id'] . '">';
    $pane_form['#suffix'] = '</div>';
    // Core bug #1988968 doesn't allow the payment method add form JS to depend
    // on an external library, so the libraries need to be preloaded here.
    foreach ($payment_gateways as $payment_gateway) {
      if ($js_library = $payment_gateway->getPlugin()->getJsLibrary()) {
        $pane_form['#attached']['library'][] = $js_library;
      }
    }

    $options = $this->paymentOptionsBuilder->buildOptions($this->order, $payment_gateways);
    $option_labels = array_map(function (PaymentOption $option) {
      return $option->getLabel();
    }, $options);
    $parents = array_merge($pane_form['#parents'], ['payment_method']);
    $default_option_id = NestedArray::getValue($form_state->getUserInput(), $parents);
    if ($default_option_id && isset($options[$default_option_id])) {
      $default_option = $options[$default_option_id];
    }
    else {
      $default_option = $this->paymentOptionsBuilder->selectDefaultOption($this->order, $options);
    }

    $pane_form['payment_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Payment method'),
      '#options' => $option_labels,
      '#default_value' => $default_option->getId(),
      '#ajax' => [
        'callback' => [parent::class, 'ajaxRefresh'],
        'wrapper' => $pane_form['#wrapper_id'],
      ],
      '#access' => count($options) > 1,
    ];
    // Add a class to each individual radio, to help themers.
    $currency_code = $this->order->getTotalPrice()->getCurrencyCode();
    foreach ($options as $option) {
      $class_name = $option->getPaymentMethodId() ? 'stored' : 'new';
      $pane_form['payment_method'][$option->getId()]['#attributes']['class'][] = "payment-method--$class_name";
      $pane_form['payment_method'][$option->getId()]['#description'] = \Drupal::service('commerce_funds.fees_manager')->printPaymentGatewayFees($option->getPaymentGatewayId(), $currency_code, 'deposit');
    }
    // Store the options for submitPaneForm().
    $pane_form['#payment_options'] = $options;

    $default_payment_gateway_id = $default_option->getPaymentGatewayId();
    $payment_gateway = $payment_gateways[$default_payment_gateway_id];

    if ($payment_gateway->getPlugin() instanceof SupportsStoredPaymentMethodsInterface) {
      $pane_form = parent::buildPaymentMethodForm($pane_form, $form_state, $default_option);
    }
    else {
      $pane_form = parent::buildBillingProfileForm($pane_form, $form_state);
    }

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    // Return parent submit function if not a deposit.
    if ($this->order->get('type')->getValue()[0]['target_id'] !== 'deposit') {
      return parent::submitPaneForm($pane_form, $form_state, $complete_form);
    }
    parent::submitPaneForm($pane_form, $form_state, $complete_form);
    // Apply the fee to the order on build if any.
    \Drupal::service('commerce_funds.fees_manager')->applyFeeToOrder($this->order);
  }

}
