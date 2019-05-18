<?php

namespace Drupal\commerce_oci_checkout\Controller;

use Drupal\commerce\Context;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_cart\Controller\CartController;
use Drupal\commerce_order\PriceCalculator;
use Drupal\commerce_price\Resolver\ChainPriceResolverInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

/**
 * Class OciCartController.
 */
class OciCartController extends CartController {

  /**
   * Attribute bag.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface
   */
  protected $attributeBag;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Current store service.
   *
   * @var \Drupal\commerce_store\CurrentStore
   */
  protected $currentStore;

  /**
   * OciCartController constructor.
   */
  public function __construct(CartProviderInterface $cart_provider,
      AttributeBagInterface $attribute_bag,
      EntityTypeManagerInterface $entity_type_manager,
      ConfigFactoryInterface $config_factory,
      ModuleHandlerInterface $module_handler,
      AccountProxyInterface $current_user,
      CurrentStoreInterface $current_store,
      PriceCalculator $price_calculator) {
    parent::__construct($cart_provider);
    $this->attributeBag = $attribute_bag;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
    $this->currentStore = $current_store;
    $this->priceCalculator = $price_calculator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_cart.cart_provider'),
      $container->get('session.attribute_bag'),
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('commerce_store.current_store'),
      $container->get('commerce_order.price_calculator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function cartPage() {
    $page = parent::cartPage();
    if (!$url = $this->attributeBag->get(CommerceOciCheckoutController::HOOK_URL_ATTRIBUTE_NAME)) {
      return $page;
    }
    // So what we are going to do now, is remove the actions, and create a
    // new form with twig, which the user then can submit.
    $cart_ids = [];
    foreach ($page as $delta => $element) {
      if (!is_array($element)) {
        continue;
      }
      if (!isset($element['#name']) || $element['#name'] !== 'commerce_cart_form') {
        continue;
      }
      $cart_ids[] = $delta;
    }
    if (empty($cart_ids)) {
      return $page;
    }
    $site_config = $this->configFactory->get('system.site');
    $items_with_fields = [];
    $adjustments = ['promotion'];
    foreach ($cart_ids as $id) {
      /** @var \Drupal\commerce_order\Entity\Order $order */
      $order = $this->entityTypeManager->getStorage('commerce_order')->load($id);
      /** @var \Drupal\commerce_order\Entity\OrderItem[] $order_items */
      $order_items = $order->getItems();
      foreach ($order_items as $item) {
        if (!$item->hasPurchasedEntity()) {
          continue;
        }
        $qty = $item->getQuantity();
        $entity = $item->getPurchasedEntity();
        $product = $entity->getProduct();
        $description = '';
        if ($product->body && !$product->get('body')->isEmpty()) {
          $description = $product->get('body')->first()->getString();
        }
        // Let the resolver convert this.
        $context = new Context($this->currentUser, $this->currentStore->getStore());
        // Create a temporary product variation.
        $variation = $this->entityTypeManager->getStorage('commerce_product_variation')->loadFromContext($product);
        $price_result = $this->priceCalculator->calculate($variation, 1, $context, $adjustments);
        $price_resolved = $price_result->getCalculatedPrice();
        $row = [
          'QUANTITY' => $qty,
          'DESCRIPTION' => $description,
          'VENDOR' => $site_config->get('name'),
          // @todo: I have no idea what this is. Figure out?
          'UNIT' => 'EA',
          'PRICE' => $price_resolved->getNumber(),
          'CURRENCY' => $price_resolved->getCurrencyCode(),
          'PRICE_UNIT' => 1,
          'VENDORMAT' => $entity->getSku(),
          'EXT_PRODUCT_ID' => $entity->getSku(),
          // @todo: No idea what this is. Figure out?
          'LEADTIME' => 10,
          'MATGROUP' => '',
        ];
        $sku = $entity->getSku();
        $this->moduleHandler->alter('commerce_oci_checkout_row', $row, $sku, $product);
        $items_with_fields[] = $row;
      }
    }
    $this->moduleHandler->alter('commerce_oci_item_rows', $items_with_fields, $order);
    $form = [];
    // Create the default "base" fields.
    $fields = [
      'RoundTripIncomingCharset' => 'UTF-8',
      '_charset_' => 'UTF-8',
      'OCIMapping.Agent' => '',
      'OCICartIncrementalOrderRelation' => 1,
      'OCICartMinimumOrderRelation' => 1,
      'OCICartUserQuantityRelation' => 1,
    ];
    $submit_button = [
      '#type' => 'submit',
      '#value' => $this->t('Go to procurement system'),
    ];
    $form['hidden_form'] = [
      '#theme' => 'commerce_oci_checkout_form',
      '#hook_url' => $url,
      '#fields' => $fields,
      '#items_with_fields' => $items_with_fields,
      '#submit_button' => $submit_button,
    ];
    $this->moduleHandler->alter('commerce_oci_checkout_form', $form);
    $page['twig_form'] = $form;
    return $page;
  }

}
