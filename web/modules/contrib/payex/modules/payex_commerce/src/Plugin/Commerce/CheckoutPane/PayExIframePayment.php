<?php

namespace Drupal\payex_commerce\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\payex\PayEx\PayExAPIException;
use Drupal\payex\Service\PayExApiFactory;
use Drupal\payex_commerce\Plugin\Commerce\PaymentGateway\PayExInterface;
use Drupal\payex_commerce\Service\PayExApiWrapper;
use Drupal\payex_commerce\Service\PayExCommerceApi;
use Drupal\payex_commerce\Service\PayExCommerceApiFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the payment information pane.
 *
 * @CommerceCheckoutPane(
 *   id = "payex_iframe_payment",
 *   label = @Translation("Complete payment"),
 *   default_step = "payment",
 *   wrapper_element = "offsite_payment",
 * )
 */
class PayExIframePayment extends CheckoutPaneBase implements ContainerFactoryPluginInterface {

  /**
   * The PayEx API factory.
   *
   * @var PayExApiFactory
   */
  protected $apiFactory;

    /**
   * Constructs a new BillingInformation object.
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
   * @param PayExCommerceApiFactory  $apiFactory
   *   The PayEx API factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, EntityTypeManagerInterface $entity_type_manager, PayExCommerceApiFactory $apiFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow, $entity_type_manager);
    $this->apiFactory = $apiFactory;
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
      $container->get('payex_commerce.api_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    /** @var PaymentGateway $payment_gateway */
    $payment_gateway = $this->order->payment_gateway->entity;
    $plugin = $payment_gateway->getPlugin();
    if ($plugin instanceof PayExInterface) {
      $config = $plugin->getConfiguration();
      /** @var PayExCommerceApi $api */
      $api = $this->apiFactory->get($config['payex_setting_id']);
      if (!$api) {
        throw new PayExAPIException('Invalid payex setting');
      }
      $url = $api->getIframeUrlForOrder($this->order);
      if (!$url) {
        drupal_set_message($this->t('An error happened while creating your payment window, please refresh or contact customer support'));
      }
      else {
        $pane_form['iframe'] = [
          '#type' => 'html_tag',
          '#tag' => 'iframe',
          '#attributes' => [
            'src' => $url,
            'width' => '100%',
            'height' => '600px',
            'id' => 'payex-commerce-iframe',
          ],
          '#attached' => [
            'library' => ['payex_commerce/iframe_pane'],
          ],
        ];
      }
      return $pane_form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    /** @var PaymentGateway $payment_gateway */
    $payment_gateway = $this->order->payment_gateway->entity;
    $plugin = $payment_gateway->getPlugin();
    if ($plugin instanceof PayExInterface) {
      $config = $plugin->getConfiguration();
      /** @var PayExCommerceApi $api */
      $api = $this->apiFactory->get($config['payex_setting_id']);
      if (!$api) {
        throw new PayExAPIException('Invalid payex setting');
      }
      if (!$api->isOrderPaid($this->order)) {
        $form_state->setError($pane_form, $this->t('Payment has not been completed'));
      }
    }
  }

}
