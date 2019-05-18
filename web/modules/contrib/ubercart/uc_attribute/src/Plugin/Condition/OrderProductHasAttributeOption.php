<?php

namespace Drupal\uc_attribute\Plugin\Condition;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesConditionBase;
use Drupal\uc_order\OrderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides 'Order has a product with a particular attribute option' condition.
 *
 * @Condition(
 *   id = "uc_attribute_ordered_product_option",
 *   label = @Translation("Order has a product with a particular attribute option"),
 *   description = @Translation("Search the products of an order for a particular attribute option."),
 *   category = @Translation("Order: Product"),
 *   context = {
 *     "order" = @ContextDefinition("entity:uc_order",
 *       label = @Translation("Order")
 *     ),
 *     "option" = @ContextDefinition("integer",
 *       label = @Translation("Attribute option"),
 *       list_options_callback = "orderedProductOptions",
 *       multiple = TRUE
 *     )
 *   }
 * )
 */
class OrderProductHasAttributeOption extends RulesConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a OrderProductHasAttributeOption object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Search the products of an order for a particular attribute option.');
  }

  /**
   * Returns an array of Attribute options.
   *
   * @return array
   *   An array of attribute options names keyed by attribute aid.
   */
  public function orderedProductOptions() {
    $options = [];
    $result = $this->database->query("SELECT a.aid, a.name AS attr_name, a.ordering, o.oid, o.name AS opt_name, o.ordering FROM {uc_attributes} a JOIN {uc_attribute_options} o ON a.aid = o.aid ORDER BY a.ordering, o.ordering");
    foreach ($result as $option) {
      $options[$option->attr_name][$option->oid] = $option->opt_name;
    }

    return $options;
  }

  /**
   * Determines if a product in the order has the selected attribute option.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order entity.
   * @param int $option_id
   *   The option identifier.
   *
   * @return bool
   *   TRUE if a product in the given order has the selected option.
   */
  protected function doEvaluate(OrderInterface $order, $option_id) {
    $option = uc_attribute_option_load($option_id);
    $attribute = uc_attribute_load($option->aid);

    foreach ($order->products as $product) {
      if (!isset($product->data['attributes'])) {
        continue;
      }

      $attributes = $product->data['attributes'];

      // Once the order is made, the attribute data is changed to just the
      // names. So if we can't find the attribute by ID, check the names.
      if (is_int(key($attributes))) {
        if (isset($attributes[$option_id])) {
          return TRUE;
        }
      }
      elseif (isset($attributes[$attribute->name][$option_id])) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
