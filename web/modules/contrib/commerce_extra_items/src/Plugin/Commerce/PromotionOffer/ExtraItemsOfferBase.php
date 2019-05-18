<?php

namespace Drupal\commerce_extra_items\Plugin\Commerce\PromotionOffer;

use Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer\OrderItemPromotionOfferBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\RounderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides the base class for Extra Item offers.
 */
abstract class ExtraItemsOfferBase extends OrderItemPromotionOfferBase implements ExtraItemsPromotionOfferBaseInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new PromotionOfferBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The pluginId for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The rounder.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RounderInterface $rounder, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $rounder);
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_price.rounder'),
      $container->get('entity_type.manager'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'quantity' => NULL,
      'purchasable_entity' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getQuantity() {
    return $this->configuration['quantity'];
  }

  /**
   * {@inheritdoc}
   */
  public function getExtraItemPurchasableEntity() {
    if ($this->configuration['purchasable_entity']) {
      $storage = $this->entityTypeManager->getStorage('commerce_product_variation');
      $entity = $storage->load($this->configuration['purchasable_entity']);
      /** @var \Drupal\commerce\PurchasableEntityInterface|null $entity */
      return $entity;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form += parent::buildConfigurationForm($form, $form_state);

    $plugin_id = $this->getPluginId();
    $purchasable_entity = $this->getExtraItemPurchasableEntity();
    $quantity = $this->getQuantity();

    $form['product'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Specify a product variation to offer'),
    ];
    $form['product']['same_purchasable_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Offer the same product variation'),
      '#description' => $this->t('Offer the same product variation as in the order item which triggered the offer.'),
      // TODO The checkbox is not ticked by default if the offer is not
      // the first in the offers list.
      '#default_value' => empty($purchasable_entity),
    ];
    $form['product']['purchasable_entity'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Select a product variation'),
      '#target_type' => 'commerce_product_variation',
      '#default_value' => $purchasable_entity,
      '#states' => [
        'invisible' => [
          ':input[name$="[target_plugin_configuration][' . $plugin_id . '][product][same_purchasable_entity]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];
    $form['quantity'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Specify a quantity of the product variation to offer'),
    ];
    $form['quantity']['parent_order_item_quantity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Offer the same quantity'),
      '#description' => $this->t('Offer the same quantity as in the order item which triggered the offer.'),
      // TODO The checkbox is not ticked by default if the offer is not
      // the first in the offers list.
      '#default_value' => empty($quantity),
    ];
    $form['quantity']['quantity'] = [
      '#type' => 'number',
      '#title' => $this->t('Quantity of the offered product'),
      '#min' => 1,
      '#max' => 9999,
      '#step' => 1,
      '#default_value' => $quantity,
      '#states' => [
        'invisible' => [
          ':input[name$="[target_plugin_configuration][' . $plugin_id . '][quantity][parent_order_item_quantity]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $this->configuration['quantity'] = NULL;
    $this->configuration['purchasable_entity'] = NULL;

    if (!$values['quantity']['parent_order_item_quantity']) {
      $this->configuration['quantity'] = $values['quantity']['quantity'];
    }
    if (!$values['product']['same_purchasable_entity']) {
      $this->configuration['purchasable_entity'] = $values['product']['purchasable_entity'];
    }
  }

  /**
   * Prepares a new "extra_item" order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $parent_order_item
   *   The parent order item which invoked the offer.
   * @param \Drupal\commerce\PurchasableEntityInterface $purchasable_entity
   *   The purchasable entity offered for extra item.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface
   *   The "extra_item" order item.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function prepareExtraItem(OrderItemInterface $parent_order_item, PurchasableEntityInterface $purchasable_entity) {
    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = $this->entityTypeManager->getStorage('commerce_order_item');
    $quantity = $this->getQuantity() ?: $parent_order_item->getQuantity();
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $order_item_storage->createFromPurchasableEntity($purchasable_entity, [
      'quantity' => $quantity,
      'type' => 'extra_item',
    ]);
    $order_item->field_parent_order_item->entity = $parent_order_item;
    return $order_item;
  }

}
