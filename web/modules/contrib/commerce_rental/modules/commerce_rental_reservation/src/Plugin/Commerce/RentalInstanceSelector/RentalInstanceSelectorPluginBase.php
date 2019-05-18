<?php

namespace Drupal\commerce_rental_reservation\Plugin\Commerce\RentalInstanceSelector;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_rental_reservation\WorkflowHelperInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class RentalInstanceSelectorPluginBase extends PluginBase implements RentalInstanceSelectorPluginInterface {

  protected $entityTypeManager;

  protected $workflowHelper;

  /**
   * Constructs a RentalInstanceSelectorPluginBase object.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, WorkflowHelperInterface $workflow_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->workflowHelper = $workflow_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('commerce_rental_reservation.workflow_helper')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function selectOrderItemInstance(OrderItemInterface $order_item) {
    return NULL;
  }

}