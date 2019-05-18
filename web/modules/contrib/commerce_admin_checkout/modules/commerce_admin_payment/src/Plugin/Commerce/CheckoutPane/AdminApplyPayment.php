<?php

namespace Drupal\commerce_admin_payment\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_admin_payment\AdminManualPaymentManagerInterface;
use Drupal\commerce_admin_payment\Plugin\Commerce\PaymentGateway\AdminManualPaymentGatewayInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the contact information pane.
 *
 * @CommerceCheckoutPane(
 *   id = "commerce_admin_manual_payment_apply",
 *   label = @Translation("Admin: Apply Manual Payments"),
 *   default_step = "order_information",
 *   wrapper_element = "fieldset",
 * )
 */
class AdminApplyPayment extends CheckoutPaneBase implements CheckoutPaneInterface {
  
  /**
   * @var \Drupal\commerce_admin_payment\AdminManualPaymentManagerInterface
   */
  protected $adminManualPaymentManager;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow = NULL) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
      $checkout_flow,
      $container->get('entity_type.manager')
    );
    $instance->setAdminManualPaymentManager($container->get('commerce_admin_payment.manager'));
    $instance->setCurrentUser($container->get('current_user'));
    return $instance;
  }

  /**
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *
   * @return $this
   */
  public function setCurrentUser(AccountProxyInterface $currentUser) {
    $this->currentUser = $currentUser;
  }

  /**
   * @param \Drupal\commerce_admin_payment\AdminManualPaymentManagerInterface $adminManualPaymentManager
   */
  public function setAdminManualPaymentManager(AdminManualPaymentManagerInterface $adminManualPaymentManager) {
    $this->adminManualPaymentManager = $adminManualPaymentManager;
  }
  

  /**
   * @inheritDoc
   */
  public function getWrapperElement() {
    return $this->configuration['wrapper_element'];
  }

  /**
   * @inheritDoc
   */
  public function getDisplayLabel() {
    return $this->configuration['display_label'];
  }


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'display_label' => $this->t('Apply Manual Payments'), 
        'wrapper_element' => 'fieldset',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    $summary[] = $this->t('Display label: @label', ['@label' => $this->configuration['display_label']]);
    $summary[] = $this->t('Wrapper element: @element', ['@element' => $this->configuration['wrapper_element']]);
    return implode(', ', $summary);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    
    $form['display_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Display label'),
      '#default_value' => $this->configuration['display_label'],
    ];
    $form['wrapper_element'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wrapper Element'),
      '#default_value' => $this->configuration['wrapper_element'],
    ];
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['display_label'] = $values['display_label'];
      $this->configuration['wrapper_element'] = $values['wrapper_element'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    if ($this->currentUser->hasPermission('apply admin payments in checkout')) {
      $payment_gateways = $this->adminManualPaymentManager->getAdminManualPaymentGateways($this->order);
      return !empty($payment_gateways);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneSummary() {
    $output = [];
    if (!$this->order->get('staged_multi_payment')->isEmpty()) {
      $staged_payments = $this->order->get('staged_multi_payment')->referencedEntities();
      $staged_admin_payments = array_filter($staged_payments, function($staged_payment) {
        return $staged_payment->getPaymentGateway()->getPlugin() instanceof AdminManualPaymentGatewayInterface;
      });
      foreach ($staged_admin_payments as $staged_payment) {
        /** @var \Drupal\commerce_multi_payment\Entity\StagedPaymentInterface $staged_payment */
        $output[] = $this->entityTypeManager->getViewBuilder('commerce_staged_multi_payment')->view($staged_payment);
      }
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form['form'] = [
      '#type' => 'commerce_admin_payment_apply_form',
      '#order_id' => $this->order->id(),
      '#element_ajax' => [
        [CheckoutFlowWithPanesBase::class, 'ajaxRefreshPanes'],
      ],
    ];
    return $pane_form;
  }

}
