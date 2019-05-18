<?php

namespace Drupal\commerce_installments\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_installments\Plugin\InstallmentPlanMethodManager;
use Drupal\commerce_payment\Plugin\Commerce\CheckoutPane\PaymentProcess;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\ManualPaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the payment process pane.
 *
 * @CommerceCheckoutPane(
 *   id = "commerce_installments_payment_process",
 *   label = @Translation("Installment payment process"),
 *   default_step = "payment",
 *   wrapper_element = "container",
 * )
 */
class InstallmentPaymentProcess extends PaymentProcess {

  /**
   * The installment plan method.
   *
   * @var \Drupal\commerce_installments\Entity\InstallmentPlanMethodInterface
   */
  protected $installmentPlanMethodStorage;

  /**
   * Skip this pane if there are no eligible installments plan methods.
   *
   * @var bool $skip
   */
  protected $skip;

  /**
   * Constructs a new CheckoutPaneBase object.
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
   * @param \Drupal\commerce_installments\Plugin\InstallmentPlanMethodManager $installment_plan_manager
   *   The installment plan manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger, InstallmentPlanMethodManager $installment_plan_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow, $entity_type_manager, $messenger);
    $this->installmentPlanManager = $installment_plan_manager;

    $this->installmentPlanMethodStorage = $this->entityTypeManager->getStorage('installment_plan_method');
    // If an installment plan isn't eligible, default to standard payment.
    if (!$this->installmentPlanMethodStorage->loadEligible($this->order)) {
      $this->skip = TRUE;
    }
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
      $container->get('messenger'),
      $container->get('plugin.manager.commerce_installment_plan_methods')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    // This pane can't be used without the InstallmentSelection pane.
    $installment_pane = $this->checkoutFlow->getPane('installment_selection');
    // This pane can't be used without the PaymentInformation pane.
    $payment_info_pane = $this->checkoutFlow->getPane('payment_information');
    return $installment_pane->isVisible() && $installment_pane->getStepId() != '_disabled' && $payment_info_pane->isVisible() && $payment_info_pane->getStepId() != '_disabled';
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    if ($this->skip || empty($this->order->getData('commerce_installments_number_payments'))) {
      return parent::buildPaneForm($pane_form, $form_state, $complete_form);
    }

    // The payment gateway is currently always required to be set.
    if ($this->order->get('payment_gateway')->isEmpty()) {
      drupal_set_message($this->t('No payment gateway selected.'), 'error');
      $this->redirectToPreviousStep();
    }

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $this->order->payment_gateway->entity;
    $payment_gateway_plugin = $payment_gateway->getPlugin();

    if ($payment_gateway_plugin instanceof OnsitePaymentGatewayInterface) {
      // Retrieve the installment selection pane. Use its configuration.
      $installment_pane = $this->checkoutFlow->getPane('installment_selection');

      /** @var \Drupal\commerce_installments\Plugin\Commerce\InstallmentPlanMethod\InstallmentPlanMethodInterface $planPlugin */
      $planPlugin = $this->installmentPlanManager->createInstance($installment_pane->getConfiguration()['installment_plan'], []);


      $numberPayments = $this->order->getData('commerce_installments_number_payments', 2);
      $planPlugin->buildInstallments($this->order, $numberPayments);

      $this->checkoutFlow->redirectToStep($this->checkoutFlow->getNextStepId($this->getStepId()));
    }
    elseif ($payment_gateway_plugin instanceof OffsitePaymentGatewayInterface) {
      drupal_set_message($this->t('Offsite payment gateway selected.'), 'error');
      $this->redirectToPreviousStep();
    }
    elseif ($payment_gateway_plugin instanceof ManualPaymentGatewayInterface) {
      drupal_set_message($this->t('Manual payment gateway selected.'), 'error');
      $this->redirectToPreviousStep();
    }

    // If we get to here, then something went wrong.
    drupal_set_message($this->t('Something went wrong in setting up your installment plan.'), 'error');
    $this->redirectToPreviousStep();
  }

}
