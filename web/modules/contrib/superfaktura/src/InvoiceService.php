<?php

namespace Drupal\superfaktura;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\Calculator;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Class InvoiceService.
 *
 * @package Drupa\superfaktura
 */
class InvoiceService {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * LoggerChannelInterface object.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Conversion array for converting from 2 to 3 lang code.
   *
   * @var array
   */
  protected $conversion;

  /**
   * InvoiceService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Superfaktura settings.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(ConfigFactoryInterface $configFactory, LoggerChannelFactoryInterface $loggerFactory, LanguageManagerInterface $languageManager) {
    $this->configFactory = $configFactory->get('superfaktura.settings');
    $this->logger = $loggerFactory->get('superfaktura');
    $this->languageManager = $languageManager;
    $this->conversion = json_decode(file_get_contents(__DIR__ . '/../resources/iso-conversion.json'), TRUE);
  }

  /**
   * Get language-specific client of the SF API.
   *
   * @param string $lang
   *   Langcode for client.
   *
   * @return \SFAPIclient
   *   Object of SF Client.
   */
  public function getSfClient(string $lang) : \SFAPIclient {
    // Load default and translated config.
    $config = $this->configFactory;
    $translated_config = $this->languageManager->getLanguageConfigOverride($lang, 'superfaktura.settings');

    // Get translated values or default values if there is no config translation.
    $values = [];
    foreach (['email', 'api_key', 'company_id'] as $key) {
      if(is_null($values[$key] = $translated_config->get($key))) {
        $values[$key] = $config->get($key);
      }
    }

    $company_id = $values['company_id'];
    if (!empty($company_id)) {
      $company_id = (int) $company_id;
    }

    $api = new \SFAPIclient($values['email'], $values['api_key'], '', 'API', $company_id);
    if ($api) {
      $this->logger->info('Succesfully created Superfaktura API object.');
    }
    else {
      $this->logger->alert('Could not create the Superfaktura object');
    }

    return $api;
  }

  /**
   * Convert from 2letter langcode to 3letter langcode.
   *
   * @param string $lang2
   *   Two-letter langcode to convert.
   *
   * @return string
   *   Converted 3 letter langcode.
   */
  public function convertLang2to3(string $lang2) {
    return $this->conversion[$lang2];
  }

  /**
   * Set Client data for Superfaktura API.
   *
   * @param \Drupal\commerce_order\Entity\Order $order
   *   Created Order.
   *
   * @return array
   *   Client data.
   */
  public function addClient(Order $order) {
    $customer_profiles = $order->getBillingProfile()->get('address')->getValue();
    $customer_profile = reset($customer_profiles);

    $client = [
      'name' => $customer_profile['given_name'] . ' ' . $customer_profile['family_name'],
      'email' => $order->getEmail(),
      'address' => $customer_profile['address_line1'],
      'city' => $customer_profile['locality'],
      'zip' => $customer_profile['postal_code'],
      'country_iso_id' => $customer_profile['country_code'],
    ];

    return $client;
  }

  /**
   * Compute Invoice due date from config.
   *
   * @param \Drupal\commerce_order\Entity\Order $order
   *   Created Order.
   *
   * @return mixed
   *   Due date in seconds.
   */
  public function computeDueDate(Order $order) {
    $due_date = $order->getPlacedTime() + (3600 * 24 * $this->configFactory->get('maturity'));

    return $due_date;
  }

  /**
   * Set Invoice data for Superfaktura API.
   *
   * @param \Drupal\commerce_order\Entity\Order $order
   *   Created Order.
   *
   * @return array
   *   Invoice data.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Being thrown if invalid entity type is provided.
   */
  public function addInvoice(Order $order) {
    $paymentStorage = \Drupal::entityTypeManager()->getStorage('commerce_payment');
    /** @var \Drupal\commerce_payment\Entity\Payment[] $payments */
    $payments = $paymentStorage->loadByProperties([
      'order_id' => $order->id(),
    ]);
    $alreadyPaid = FALSE;
    foreach ($payments as $payment) {
      if ($payment->getState()->value === 'completed') {
        $alreadyPaid = TRUE;
        break;
      }
    }

    $invoice = [
      'name' => $this->configFactory->get('invoice_name_prefix') . $order->getOrderNumber(),
      'variable' => sprintf("%'.08d", $order->getOrderNumber()),
      'constant' => $this->configFactory->get('constant'),
      'specific' => $this->configFactory->get('specific'),
      'already_paid' => $alreadyPaid,
      'invoice_currency' => $order->getTotalPrice()->getCurrencyCode(),
      'invoice_no_formatted' => '',
      'created' => date('Y-m-d', $order->getPlacedTime()),
      'due' => date('Y-m-d', $this->computeDueDate($order)),
      'comment' => '',
      'type' => 'regular',
    ];

    return $invoice;
  }

  /**
   * Set Order Item data for Superfaktura API.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $item
   *   Order item.
   * @param int $orderDiscount
   *   Percentage value of total Order Discount.
   * @param string $order_discount_label
   *   Label for order discount.
   *
   * @return array
   *   Order Item data.
   *
   * @todo Set 'unit' in order_item dynamically based on item type
   */
  public function addOrderItem(OrderItemInterface $item, $orderDiscount = 0, $order_discount_label = NULL) {
    $unit_price = 0;
    $tax_rate = 0;
    $itemDiscount = 0;
    $item_price = Calculator::round($item->getUnitPrice()->getNumber(), 4, PHP_ROUND_HALF_UP);

    $item_adjustments = $item->getAdjustments();
    foreach ($item_adjustments as $item_adjustment) {
      if ($item_adjustment->getType() == 'tax') {
        $tax_percentage = $item_adjustment->getPercentage();
        $tax_rate = Calculator::multiply($tax_percentage, 100);
      }
      elseif ($item_adjustment->getType() == 'promotion') {
        // Add shipping discount percentage to shipping item.
        if (!is_null($item_adjustment->getPercentage())) {
          $itemDiscount = Calculator::multiply($item_adjustment->getPercentage(), 100);
          $order_item_discount_description = $item_adjustment->getLabel();
        }
        elseif (is_null($item_adjustment->getPercentage()) && !is_null($item_adjustment->getAmount()->getNumber())) {
          $item_discount_amount = Calculator::round($item_adjustment->getAmount()->getNumber(), 4, PHP_ROUND_HALF_UP);
          $order_item_discount_description = $item_adjustment->getLabel();
          if (strcmp(abs($item_price), abs($item_discount_amount)) === 0) {
            $itemDiscount = 100;
          }
          else {
            $item_discount_percentage = Calculator::divide($item_discount_amount, $item_price, 4);
            $itemDiscount = abs(Calculator::multiply($item_discount_percentage, 100));
          }
        }
      }
    }

    $totalDiscount = 0;
    // Calculate percentage of total discount.
    if ($orderDiscount != 0) {
      $totalDiscount = 100 - ((1 - ($itemDiscount / 100)) * (1 - ($orderDiscount / 100)) * 100);
      $order_item_discount_description = $order_discount_label;
    }
    else {
      $totalDiscount = $itemDiscount;
    }

    // If there are no tax adjustments, then we would end up without price.
    if ($tax_rate === 0) {
      $unit_price = $item->getUnitPrice()->getNumber();
    }
    else {
      // Calculate and round tax amount (ta)
      // and unit price without tax from order item.
      $unit_price = $item->getUnitPrice()->getNumber();
      $ta_divisor = Calculator::add(100, $tax_rate);
      $ta_divide = Calculator::divide($unit_price, $ta_divisor);
      $ta_multiply = Calculator::multiply($ta_divide, $tax_rate);
      $tax_amount = Calculator::round($ta_multiply, 4, PHP_ROUND_HALF_UP);

      $unit_price = Calculator::subtract($unit_price, $tax_amount, 4);
    }

    $order_item = [
      'name' => $item->getTitle(),
      'description' => '',
      'quantity' => $item->getQuantity(),
      'unit' => 'ks.',
      'unit_price' => $unit_price,
      'tax' => $tax_rate,
      'discount' => !empty($totalDiscount) ? $totalDiscount : 0,
      'discount_description' => isset($order_item_discount_description) ? $order_item_discount_description : NULL,
    ];

    return $order_item;
  }

  /**
   * Set Shipping data for Superfaktura API.
   *
   * @param \Drupal\commerce_order\Adjustment $shipping_adjustment
   *   Order shipping adjustment.
   * @param array $shipping_data
   *   Shipping data.
   *
   * @return array
   *   Shipping item data.
   */
  public function addShippingItem(Adjustment $shipping_adjustment, array $shipping_data) {
    $shipping = $shipping_adjustment->getAmount()->getNumber();

    // Calculate shipping tax amount and tax rate.
    $tax_rate = Calculator::multiply($shipping_data['shipping_tax_percentage'], 100);
    $shipping_tax_amount = Calculator::divide($shipping, (100 + $tax_rate)) * $tax_rate;

    $shipping_unit_price = Calculator::subtract($shipping, (string) $shipping_tax_amount, 4);

    $shipping_item = [
      'name' => $shipping_adjustment->getLabel(),
      'description' => '',
      'quantity' => 1,
      'unit' => 'ks.',
      'unit_price' => $shipping_unit_price,
      'tax' => $tax_rate,
    ];

    // Add shipping discount percentage to shipping item.
    if (!is_null($shipping_data['shipping_discount_percentage'])) {
      $shipping_discount_percentage = Calculator::multiply($shipping_data['shipping_discount_percentage'], 100);
      $shipping_item['discount'] = $shipping_discount_percentage;
      $shipping_item['discount_description'] = $shipping_data['shipping_discount_label'];
    }
    elseif (is_null($shipping_data['shipping_discount_percentage']) && !is_null($shipping_data['shipping_discount_amount'])) {
      $shipping_price = Calculator::round($shipping, 4, PHP_ROUND_HALF_UP);
      $shipping_discount_amount = Calculator::round($shipping_data['shipping_discount_amount'], 4, PHP_ROUND_HALF_UP);
      $shipping_item['discount_description'] = $shipping_data['shipping_discount_label'];
      if (strcmp(abs($shipping_price), abs($shipping_discount_amount)) === 0) {
        $shipping_item['discount'] = 100;
      }
      else {
        $shipping_discount_percentage = Calculator::divide($shipping_discount_amount, $shipping_price, 4);
        $shipping_item['discount'] = abs(Calculator::multiply($shipping_discount_percentage, 100));
      }
    }

    return $shipping_item;
  }

  /**
   * Create Invoice using Superfaktura API.
   *
   * @param \Drupal\commerce_order\Entity\Order $order
   *   Created Order.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Being thrown if invalid entity type is provided.
   */
  public function createInvoice(Order $order) {
    $order_discount = 0;
    $order_discount_label = NULL;

    $api = $this->getSfClient($this->languageManager->getCurrentLanguage()->getId());

    $api->setClient($this->addClient($order));
    $api->setInvoice($this->addInvoice($order));

    // Define shipping tax amount and percentage,
    // shipping promotions amount and percentage default values
    // as shipping_data.
    $shipping_data = [
      'shipping_discount_label' => '',
      'shipping_discount_amount' => NULL,
      'shipping_discount_percentage' => NULL,
      'shipping_tax_amount' => '0',
      'shipping_tax_percentage' => '0',
    ];

    $order_adjustments = $order->getAdjustments();
    /** @var \Drupal\commerce_order\Adjustment $order_adjustment */
    foreach ($order_adjustments as $order_adjustment) {
      if ($order_adjustment->getType() == 'shipping') {
        $shipping_adjustment = $order_adjustment;
      }
      elseif ($order_adjustment->getType() == 'tax') {
        // Get shipping tax amount and percentage from order adjustment.
        $shipping_data['shipping_tax_amount'] = $order_adjustment->getAmount()->getNumber();
        $shipping_data['shipping_tax_percentage'] = $order_adjustment->getPercentage();
      }
      elseif ($order_adjustment->getType() == 'shipping_promotion') {
        $shipping_data['shipping_discount_label'] = $order_adjustment->getLabel();
        $shipping_data['shipping_discount_amount'] = $order_adjustment->getAmount()->getNumber();
        if (!is_null($order_adjustment->getPercentage())) {
          $shipping_data['shipping_discount_percentage'] = $order_adjustment->getPercentage();
        }
      }
      elseif ($order_adjustment->getType() == 'promotion') {
        $api->setInvoice('discount_total', 0);
        $api->setInvoice('discount', 0);
        if (!is_null($order_adjustment->getPercentage())) {
          $order_discount = $order_adjustment->getPercentage() * 100;
          $order_discount_label = $order_adjustment->getLabel();
        }
        else {
          $api->setInvoice('discount_total', $order_adjustment->getAmount()->getNumber());
        }
      }
    }

    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $item */
    foreach ($order->getItems() as $item) {
      $api->addItem($this->addOrderItem($item, $order_discount, $order_discount_label));
    }

    if (isset($shipping_adjustment)) {
      $api->addItem($this->addShippingItem($shipping_adjustment, $shipping_data));
    }

    $response = $api->save();

    if ($response->error === 0) {
      $invoice_id = $response->data->Invoice->id;
      $this->logger->info('Invoice #@invoice was successfully created.', ['@invoice' => $invoice_id]);
      $order->set('superfaktura_invoice_id', $invoice_id);
    }
    else {
      switch ($response->error) {
        case 2:
          $this->logger->error('Data not sent by POST method');
          break;
        case 3:
          $this->logger->error('Incorrect data. Sent data is not in the correct format.');
          break;
        case 5:
          $this->logger->error('Validation error. Mandatory data is missing or incorrectly filled.');
          break;
      }
    }
  }

}
