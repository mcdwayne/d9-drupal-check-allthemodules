<?php

namespace Drupal\commerce_recurring_metered\Plugin\Commerce\SubscriptionType;

use Drupal\commerce_recurring\BillingPeriod;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;
use Drupal\commerce_recurring\Plugin\Commerce\SubscriptionType\SubscriptionTypeBase;
use Drupal\commerce_recurring_metered\UsageProxyInterface;
use Drupal\commerce_recurring_metered\UsageTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the subscription base class.
 */
abstract class MeteredBillingBase extends SubscriptionTypeBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The usage type manager.
   *
   * @var \Drupal\commerce_recurring_metered\UsageTypeManager
   */
  protected $usageTypeManager;

  /**
   * The usage proxy.
   *
   * @var \Drupal\commerce_recurring_metered\UsageProxyInterface
   */
  protected $usageProxy;

  /**
   * Constructs a new SubscriptionTypeBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_recurring_metered\UsageTypeManager $usage_type_manager
   *   The usage type manager.
   * @param \Drupal\commerce_recurring_metered\UsageProxyInterface $usage_proxy
   *   The usage proxy.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, UsageTypeManager $usage_type_manager, UsageProxyInterface $usage_proxy) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager);

    $this->usageTypeManager = $usage_type_manager;
    $this->usageProxy = $usage_proxy;
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
      $container->get('plugin.manager.commerce_usage_type'),
      $container->get('commerce_recurring_metered.usage_proxy')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function collectCharges(SubscriptionInterface $subscription, BillingPeriod $billing_period) {
    $charges = parent::collectCharges($subscription, $billing_period);
    $usage_charges = $this->usageProxy->collectCharges($subscription, $billing_period);

    return array_merge($charges, $usage_charges);
  }

}
