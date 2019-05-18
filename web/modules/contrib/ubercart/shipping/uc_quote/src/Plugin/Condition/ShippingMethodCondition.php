<?php

namespace Drupal\uc_quote\Plugin\Condition;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesConditionBase;
use Drupal\uc_order\OrderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'Order shipping method' condition.
 *
 * @Condition(
 *   id = "uc_quote_condition_order_shipping_method",
 *   label = @Translation("Order has a shipping quote from a particular method"),
 *   category = @Translation("Order: Shipping Quote"),
 *   context = {
 *     "order" = @ContextDefinition("entity:uc_order",
 *       label = @Translation("Order")
 *     ),
 *     "method" = @ContextDefinition("string",
 *       label = @Translation("Shipping method"),
 *       list_options_callback = "shippingMethodOptions"
 *     )
 *   }
 * )
 */
class ShippingMethodCondition extends RulesConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t("Order has a shipping quote from a particular method");
  }

  /**
   * Constructs a ShippingMethodCondition object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * Shipping options callback.
   *
   * @return array
   *   Array of all enabled shipping methods.
   */
  public function shippingMethodOptions() {
    $options = [];
    $methods = $this->entityTypeManager->getStorage('uc_quote_method')->loadByProperties(['status' => TRUE]);
    uasort($methods, 'Drupal\uc_quote\Entity\ShippingQuoteMethod::sort');
    foreach ($methods as $method) {
      $options[$method->id()] = $method->label();
    }

    return $options;
  }

  /**
   * Checks an order's shipping method.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order.
   * @param string $method
   *   Name of shipping method.
   *
   * @return bool
   *   TRUE if the order was placed with the selected shipping method.
   */
  protected function doEvaluate(OrderInterface $order, $method) {
    // Check the easy way first.
    if (!empty($order->quote)) {
      return $order->quote['method'] == $method;
    }
    // Otherwise, look harder.
    if (!empty($order->line_items)) {
      $methods = $this->moduleHandler->invokeAll('uc_shipping_method');
      $accessorials = $methods[$method]['quote']['accessorials'];

      foreach ($order->line_items as $line_item) {
        if ($line_item['type'] == 'shipping' && in_array($line_item['title'], $accessorials)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
