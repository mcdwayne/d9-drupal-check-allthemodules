<?php

namespace Drupal\commerce_swisscom_easypay;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_swisscom_easypay\Event\CheckoutPageItemEvent;
use Drupal\commerce_swisscom_easypay\Event\SwisscomEasypayEvents;
use Drupal\Core\Language\LanguageManagerInterface;
use Gridonic\EasyPay\CheckoutPage\CheckoutPageItem;
use Gridonic\EasyPay\CheckoutPage\CheckoutPageService as EasypayCheckoutService;
use Gridonic\EasyPay\Environment\Environment;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Service to construct the url of the checkout page for the redirect.
 *
 * @package Drupal\commerce_swisscom_easypay
 */
class CheckoutPageService {

  /**
   * The configuration data from the plugin.
   *
   * @var array
   */
  private $pluginConfig;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  private $eventDispatcher;

  /**
   * Drupal's language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private $languageManager;

  /**
   * PaymentRequestService constructor.
   *
   * @param array $pluginConfig
   *   The plugin configuration.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   Drupal's language manager.
   */
  public function __construct(array $pluginConfig, EventDispatcherInterface $eventDispatcher, LanguageManagerInterface $languageManager) {
    $this->pluginConfig = $pluginConfig;
    $this->eventDispatcher = $eventDispatcher;
    $this->languageManager = $languageManager;
  }

  /**
   * Build the url of the checkout page for the given order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The commerce order.
   * @param array $form
   *   The payment offsite form data.
   *
   * @throws PaymentGatewayException
   *
   * @return string
   *   The url of the checkout page for the redirect.
   */
  public function getCheckoutPageUrl(OrderInterface $order, array $form) {
    $checkoutPageItem = new CheckoutPageItem();
    $checkoutPageItem
      ->setTitle($this->pluginConfig['checkout_page_title'])
      ->setDescription($this->getCheckoutPageDescription($order))
      ->setPaymentInfo($this->pluginConfig['payment_info'])
      ->setAmount($this->formatTotalPrice($order))
      ->setSuccessUrl($form['#return_url'])
      ->setErrorUrl($form['#return_url'])
      ->setCancelUrl($form['#cancel_url'])
      ->setCpUserId($order->getCustomerId() ?? '')
      ->setCpServiceId($order->id())
      ->setUserLanguage($this->languageManager->getCurrentLanguage()->getId());

    if ($this->pluginConfig['checkout_page_image_url']) {
      $checkoutPageItem->setImageUrl($this->pluginConfig['checkout_page_image_url']);
    }

    $environment = $this->getEnvironment();
    $checkoutPageService = EasypayCheckoutService::create($environment);

    $checkoutPageItem = $this->dispatchEvent($checkoutPageItem, $order);

    try {
      return $checkoutPageService->getCheckoutPageUrl($checkoutPageItem);
    }
    catch (\DomainException $e) {
      throw new PaymentGatewayException($e->getMessage(), 0, $e);
    }
  }

  /**
   * Dispatch an event allowing to modify the data of the checkout page item.
   *
   * @param \Gridonic\EasyPay\CheckoutPage\CheckoutPageItem $checkoutPageItem
   *   The current checkout page item.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The commerce order.
   *
   * @return \Gridonic\EasyPay\CheckoutPage\CheckoutPageItem
   *   The checkout page item with (possibly) modified data.
   */
  protected function dispatchEvent(CheckoutPageItem $checkoutPageItem, OrderInterface $order) {
    $event = new CheckoutPageItemEvent($order);
    $event->setData($checkoutPageItem->toArray());
    $this->eventDispatcher->dispatch(SwisscomEasypayEvents::CHECKOUT_PAGE_ITEM, $event);

    return new CheckoutPageItem($event->getData());
  }

  /**
   * Format the order's total price to the expected format <INT>.<FRACTION>.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The commerce order.
   *
   * @return string
   *   The total price of the order in the expected format.
   */
  protected function formatTotalPrice(OrderInterface $order) {
    return number_format((float) $order->getTotalPrice()
      ->getNumber(), 2, '.', '');
  }

  /**
   * Build the description displayed on the checkout page.
   *
   * If the description from the plugin config is empty,
   * we construct a summary of all products of the order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The commerce order.
   *
   * @return string
   *   Description displayed on the checkout page.
   */
  protected function getCheckoutPageDescription(OrderInterface $order) {
    $description = $this->pluginConfig['checkout_page_description'];

    if (!$description) {
      $orderItems = array_map(function ($orderItem) {
        /* @var $orderItem \Drupal\commerce_order\Entity\OrderItemInterface */
        return sprintf('%s x %s', (int) $orderItem->getQuantity(), $orderItem->getTitle());
      }, $order->getItems());
      $description = implode(' / ', $orderItems);
    }

    return $description;
  }

  /**
   * Get the Easypay prod or staging environment based on the plugin config.
   *
   * @return \Gridonic\EasyPay\Environment\Environment
   *   Prod or stating environment depending on configuration.
   */
  protected function getEnvironment() {
    $type = ($this->pluginConfig['mode'] === 'live') ? Environment::ENV_PROD : Environment::ENV_STAGING;

    return new Environment($type, $this->pluginConfig['merchant_id'], $this->pluginConfig['secret']);
  }

}
