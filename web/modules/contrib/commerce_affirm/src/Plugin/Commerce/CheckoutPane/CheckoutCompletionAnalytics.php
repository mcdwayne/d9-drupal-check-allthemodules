<?php

namespace Drupal\commerce_affirm\Plugin\Commerce\CheckoutPane;

use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\commerce_affirm\MinorUnitsInterface;

/**
 * Provides the Affirm checkout completion analytics pane.
 *
 * @CommerceCheckoutPane(
 *   id = "affirm_checkout_completion_analytics",
 *   label = @Translation("Affirm checkout completion analytics"),
 *   default_step = "complete",
 * )
 */
class CheckoutCompletionAnalytics extends CheckoutPaneBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config_factory;

  /**
   * The minor units service.
   *
   * @var \Drupal\commerce_affirm\MinorUnitsInterface
   */
  protected $minor_units;

  /**
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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\commerce_affirm\MinorUnitsInterface $minor_units
   *   The minor units service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, MinorUnitsInterface $minor_units) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow, $entity_type_manager);
    $this->config_factory = $config_factory;
    $this->minor_units = $minor_units;
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
      $container->get('config.factory'),
      $container->get('commerce_affirm.minor_units')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $analytics = $this->config_factory->get('commerce_affirm.settings')->get('analytics');
    if ($analytics && empty($this->order->getData('commerce_affirm_analytics_sent', 0))) {
      $order_total = $this->order->getTotalPrice();
      $data = [
        'checkoutComplete' => [
          'order' => [
            'orderId' => $this->order->id(),
            'currency' => $order_total->getCurrencyCode(),
            'total' => $this->minor_units->toMinorUnits($order_total),
            'storeName' => $this->order->getStore()->label(),
            'paymentMethod' => $this->order->get('payment_gateway')->entity->getPlugin()->getLabel(),
          ],
        ],
      ];
      foreach ($this->order->getItems() as $item) {
        $data['checkoutComplete']['products'][] = [
          'price' => $this->minor_units->toMinorUnits($item->getTotalPrice()),
          'productId' => $item->getPurchasedEntityId(),
          'quantity' => $item->getQuantity(),
        ];
      }

      $pane_form['#attached']['drupalSettings']['commerceAffirmAnalytics'] = $data;
      $pane_form['#attached']['library'][] = 'commerce_affirm/affirm-checkout-analytics';
      $this->order->setData('commerce_affirm_analytics_sent', TRUE);
      $this->order->save();
    }
    return $pane_form;
  }

}
