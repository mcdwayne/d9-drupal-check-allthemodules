<?php

namespace Drupal\commerce_inventory\Plugin\views\field;

use Drupal\commerce_inventory\QuantityManagerInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to present the available quantity of an Inventory Item.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("commerce_inventory_item_quantity_available")
 */
class CommerceInventoryItemQuantityAvailable extends FieldPluginBase {

  /**
   * The quantity on-hand manager.
   *
   * @var \Drupal\commerce_inventory\QuantityManagerInterface
   */
  protected $quantityOnHand;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QuantityManagerInterface $quantity_on_hand) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->quantityOnHand = $quantity_on_hand;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_inventory.quantity_available')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['id'] = 'id';
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $id = $this->getValue($values, 'id');
    return $this->quantityOnHand->getQuantity($id);
  }

}
