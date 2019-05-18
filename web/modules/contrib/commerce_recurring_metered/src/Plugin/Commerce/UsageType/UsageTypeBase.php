<?php

namespace Drupal\commerce_recurring_metered\Plugin\Commerce\UsageType;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_recurring\BillingPeriod;
use Drupal\commerce_recurring\Charge;
use Drupal\commerce_recurring\Entity\Subscription;
use Drupal\commerce_recurring\RecurringOrderManagerInterface;
use Drupal\commerce_recurring_metered\UsageRecordStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Usage type plugin base class.
 *
 * Implements logic which is likely to be shared between all implementations.
 */
abstract class UsageTypeBase extends PluginBase implements UsageTypeInterface, ContainerFactoryPluginInterface {

  /**
   * The usage record storage class.
   *
   * @var \Drupal\commerce_recurring_metered\UsageRecordStorageInterface
   */
  protected $storage;

  /**
   * The subscription entity which owns this instance of the usage group.
   *
   * @var \Drupal\commerce_recurring\Entity\SubscriptionInterface
   */
  protected $subscription;

  /**
   * The recurring order manager.
   *
   * Providing this here saves the core plugins from having to override
   * \Drupal\Core\Plugin\ContainerFactoryPluginInterface::create(), and it's
   * a service many plugins are likely to need.
   *
   * @var \Drupal\commerce_recurring\RecurringOrderManagerInterface
   */
  protected $recurringOrderManager;

  /**
   * Instantiate a new usage type plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\commerce_recurring_metered\UsageRecordStorageInterface $storage
   *   The storage handler.
   * @param \Drupal\commerce_recurring\RecurringOrderManagerInterface $recurring_order_manager
   *   The recurring order manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, TranslationInterface $string_translation, UsageRecordStorageInterface $storage, RecurringOrderManagerInterface $recurring_order_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->stringTranslation = $string_translation;
    $this->storage = $storage;
    $this->recurringOrderManager = $recurring_order_manager;

    if (!isset($configuration['subscription'])) {
      throw new \InvalidArgumentException($this->t("You must pass the subscription as part of the plugin configuration (the 'subscription' array key)."));
    }
    $this->subscription = $configuration['subscription'];

    // We have to make sure that the subscription implements the necessary
    // interfaces to work with these usage groups.
    foreach ($this->requiredSubscriptionTypeInterfaces() as $interface) {
      if (!($this->subscription->getType() instanceof $interface)) {
        throw new \LogicException('Usage groups of type ' . static::class . ' can only be attached to subscription types which implement ' . $interface);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation'),
      $container->get('commerce_recurring_metered.storage.usage_record'),
      $container->get('commerce_recurring.order_manager')
    );
  }

  /**
   * Get the subscription of this usage type.
   *
   * @return \Drupal\commerce_recurring\Entity\SubscriptionInterface
   *   The subscription against which we're operating.
   */
  public function getSubscription() {
    return $this->subscription;
  }

  /**
   * The default behavior is for usage groups to not enforce change scheduling.
   */
  public function enforceChangeScheduling($property, $oldValue, $newValue) {
    return FALSE;
  }

  /**
   * Whether we have enough usage info.
   *
   * The default behavior is to regard usage as complete. Usage types with
   * remote storage or record completeness requirements override this method.
   *
   * {@inheritdoc}
   */
  public function isComplete(BillingPeriod $period) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\commerce_recurring_metered\UsageRecordInterface[]
   *   The usage records.
   */
  public function usageHistory(BillingPeriod $period) {
    // Here we fetch the records from storage, and then massage them to line
    // up with the start and end of the billing cycle.
    $records = $this->storage->fetchPeriodRecords($this->subscription, $period, $this->getPluginId());
    $periodStart = $period->getStartDate()->getTimestamp();
    $periodEnd = $period->getEndDate()->getTimestamp();

    foreach ($records as $record) {
      if ($record->getStart() < $periodStart) {
        $record->setStart($periodStart);
      }
      $end = $record->getEnd();
      if ($end === NULL || $end > $periodEnd) {
        $record->setEnd($periodEnd);
      }
    }

    return $records;
  }

  /**
   * {@inheritdoc}
   */
  public function onSubscriptionChange(Subscription $subscription) {

  }

  /**
   * Helper function to generate a charge object.
   *
   * This method is intended to be overridden if you need to change things like
   * the title of the charge.
   *
   * @param int $quantity
   *   How many to charge for.
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   What to charge for (the product variation used for charging for usage).
   * @param \Drupal\commerce_recurring\BillingPeriod $period
   *   The billing period to which the charge applies.
   * @param \Drupal\commerce_recurring\BillingPeriod $full_period
   *   The full billing period from the order.
   *
   * @return \Drupal\commerce_recurring\Charge
   *   A \Drupal\commerce_recurring\Charge representing this specific
   *   variation's usage during this billing period.
   */
  protected function generateCharge($quantity, ProductVariationInterface $variation, BillingPeriod $period, BillingPeriod $full_period) {
    return new Charge([
      'title' => $variation->getTitle(),
      'unit_price' => $variation->getPrice(),
      'quantity' => $quantity,
      'billing_period' => $period,
      'purchased_entity' => $variation,
      'full_billing_period' => $full_period,
    ]);
  }

}
