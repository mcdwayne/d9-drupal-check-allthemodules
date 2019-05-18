<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2018 Lamia Oy (https://lamia.fi)
 */


namespace Drupal\commerce_nordea\PluginForm;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\commerce_nordea\DependencyInjection\PaymentHelper;
use Drupal\commerce_nordea\Plugin\Commerce\PaymentGateway\NordeaPayment;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Verifone\Core\DependencyInjection\Service\CustomerImpl;
use \Verifone\Core\DependencyInjection\Service\OrderImpl;
use \Verifone\Core\DependencyInjection\Service\AddressImpl;
use \Verifone\Core\DependencyInjection\Configuration\Frontend\FrontendConfigurationImpl;
use \Verifone\Core\DependencyInjection\Configuration\Frontend\RedirectUrlsImpl;
use \Verifone\Core\DependencyInjection\Service\PaymentInfoImpl;
use Verifone\Core\DependencyInjection\Service\ProductImpl;
use \Verifone\Core\DependencyInjection\Service\TransactionImpl;
use \Verifone\Core\Service\Frontend\CreateNewOrderService;
use \Verifone\Core\ExecutorContainer;
use \Verifone\Core\ServiceFactory;


class NordeaOffsiteForm extends PaymentOffsiteForm implements ContainerInjectionInterface
{
  use StringTranslationTrait;

  /** @var PaymentHelper */
  protected $_paymentHelper;

  public function __construct(PaymentHelper $paymentHelper)
  {
    $this->_paymentHelper = $paymentHelper;
  }

  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('commerce_nordea.payment_helper')
    );
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state)
  {
    $form = parent::buildConfigurationForm($form, $form_state);
    $configuration = $this->_getConfiguration();
    $defaultConfiguration = $this->_getDefaultConfiguration();

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    /** @var \Drupal\commerce_nordea\Plugin\Commerce\PaymentGateway\NordeaPayment $paymentGatewayPlugin */
    $paymentGatewayPlugin = $payment->getPaymentGateway()->getPlugin();

    /** @var  \Drupal\commerce_order\Entity\Order $order */
    $order = $form_state->getFormObject()->getOrder();

    $shopKeyFilePath = $this->_paymentHelper->getKeyPath($this->entity->getPaymentGatewayId(), $configuration, $defaultConfiguration, $this->_paymentHelper::KEY_FILE_SHOP);

    $urls = new RedirectUrlsImpl(
      $paymentGatewayPlugin->getReturnUrl($order),
      $paymentGatewayPlugin->getCancelUrl($order, 'rejected'),
      $paymentGatewayPlugin->getCancelUrl($order, 'cancel'),
      $paymentGatewayPlugin->getCancelUrl($order, 'expired'),
      $paymentGatewayPlugin->getCancelUrl($order, 'error')
    );

    //configuration object
    $configObject = new FrontendConfigurationImpl(
      $urls,
      $shopKeyFilePath,
      $this->_paymentHelper->getMerchantId($this->entity->getPaymentGatewayId(), $configuration, $defaultConfiguration),
      $this->_paymentHelper->getSystemName(),
      $this->_paymentHelper->getModuleVersion(),
      $configuration['skip_confirmation_page'],
      $configuration['disable_rsa_blinding'],
      $configuration['style_code']
    );

    // address
    /** @var \Drupal\address\AddressInterface $billingAddress */
    $billingAddress = $order->getBillingProfile()->address->first();

    $externalField = $configuration['customer_external_id'];
    $phoneNumber = '';
    $externalFieldValue = $order->getEmail();

    if (!empty($externalField)) {
      try {
        $phoneNumber = $billingAddress->getEntity()->get($externalField)->first()->getValue()['value'];
        $externalFieldValue = '';
      } catch (\Exception $e) {
      }
    }

    $address = new AddressImpl(
      $billingAddress->getAddressLine1(),
      $billingAddress->getAddressLine2(),
      '',
      $billingAddress->getLocality(),
      $billingAddress->getPostalCode(),
      $this->_paymentHelper->convertCountryCode2Numeric($billingAddress->getCountryCode())
    );

    // customer
    $customer = new CustomerImpl(
      $this->sanitize($billingAddress->getGivenName()),
      $this->sanitize($billingAddress->getFamilyName()),
      $this->sanitize($phoneNumber),
      $this->sanitize($order->getEmail()),
      $this->sanitize($externalFieldValue),
      $address
    );

    $totalTax = 0;

    foreach ($order->collectAdjustments() as $adjustment) {
      if ($adjustment->getType() === 'tax') {
        $totalTax += $adjustment->getAmount()->getNumber();
      }
    }

    $totalInclTax = $payment->getAmount()->getNumber();
    $totalExclTax = $totalInclTax - $totalTax;

    // order information
    $orderImpl = new OrderImpl(
      (string)$order->id(),
      gmdate('Y-m-d H:i:s', $order->getCreatedTime()),
      $this->_paymentHelper->convertCountryToISO4217($payment->getAmount()->getCurrencyCode()),
      (string)(round($totalInclTax, 2) * 100),
      (string)(round($totalExclTax, 2) * 100),
      (string)(round($totalTax, 2) * 100)
    );

//    var_dump($orderImpl);die();

    $productsData = $this->_prepareProducts($order, $orderImpl);

    $products = [];

    foreach ($productsData as $productData) {
      $products[] = new ProductImpl(
        $this->sanitize($productData['name']),
        (string)$productData['unit_cost'],
        (string)$productData['net_amount'],
        (string)$productData['gross_amount'],
        (string)$productData['unit_count'],
        (string)$productData['discount_percentage']
      );
    }

    // payment information
    $savePaymentMethod = PaymentInfoImpl::SAVE_METHOD_AUTO_NO_SAVE;
//    if ($configuration['allow_to_save_cc']) {
//      $savePaymentMethod = PaymentInfoImpl::SAVE_METHOD_NORMAL;
//    }

    switch (\Drupal::languageManager()->getCurrentLanguage()->getId()) {
      case 'en':
        $language = 'en_GB';
        break;
      case 'fi':
        $language = 'fi_FI';
        break;
      case 'sv':
        $language = 'sv_SE';
        break;
      case 'nb':
      case 'nn':
        $language = 'no_NO';
        break;
      case 'da':
        $language = 'dk_DK';
        break;
      default:
        $language = $configuration['payment_page_language'];
        break;
    }

    $paymentInfo = new PaymentInfoImpl(
      $language,
      $savePaymentMethod
    );

    $transactionInfo = new TransactionImpl('', '');

    /** @var CreateNewOrderService $service */
    $service = ServiceFactory::createService($configObject, 'Frontend\CreateNewOrderService');
    $service->insertCustomer($customer);
    $service->insertOrder($orderImpl);
    $service->insertPaymentInfo($paymentInfo);
    $service->insertTransaction($transactionInfo);

    foreach ($products as $product) {
      $service->insertProduct($product);
    }

    // for json: new ExecutorContainer(array('requestConversion.class' => ExecutorContainer::REQUEST_CONVERTER_TYPE_JSON));
    $container = new ExecutorContainer();
    $exec = $container->getExecutor(ExecutorContainer::EXECUTOR_TYPE_FRONTEND);

    $data = $exec->executeService($service, $this->_paymentHelper->getUrls($configuration, 'page'), $configuration['validate_url']);

    $action = $data['action'];
    unset($data['action']);

    return $this->buildRedirectForm(
      $form,
      $form_state,
      $action,
      $data,
      PaymentOffsiteForm::REDIRECT_POST
    );
  }

  /**
   * @return array
   */
  protected function _getConfiguration()
  {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    /** @var \Drupal\commerce_nordea\Plugin\Commerce\PaymentGateway\NordeaPayment $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    return $payment_gateway_plugin->getConfiguration();
  }

  protected function _getDefaultConfiguration()
  {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    /** @var \Drupal\commerce_nordea\Plugin\Commerce\PaymentGateway\NordeaPayment $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    return $payment_gateway_plugin->defaultConfiguration();
  }

  /**
   * Remove unexpected characters
   *
   * @param $string
   * @return mixed
   */
  public function sanitize($string)
  {
    return str_replace('"', '', str_replace('\\', '', str_replace('-', ' ', $string)));
  }

  protected function _prepareProducts(OrderInterface $order, OrderImpl $orderImpl)
  {
    $items = $order->getItems();

    if (!$this->_isSendBasketItems()) {
      return [];
    }

    $products = [];
    $itemsTax = $itemsNetPrice = $itemsGrossPrice = 0;

    foreach ($items as $orderItem) {
      $product = $this->_getBasketItemData($orderItem);

      $products[] = $product;

      $itemsTax += $product['gross_amount'] - $product['net_amount'];
      $itemsNetPrice += $product['net_amount'];
      $itemsGrossPrice += $product['gross_amount'];

    }

    $taxProducts = [];

    if (\count($products) > NordeaPayment::BASKET_LIMIT) {
      foreach ($products as $product) {

        $tax = $product['tax_percentage'] / 100;

        if (!isset($taxProducts[$tax])) {
          $taxProducts[$tax] = $product;
          $taxProducts[$tax]['count'] = 1;
          $taxProducts[$tax]['unit_cost'] = $product['net_amount'];
          $taxProducts[$tax]['name'] = sprintf($this->t('Multiple items - tax %s'), $tax);
        } else {
          $taxProducts[$tax]['unit_cost'] += $product['net_amount'];
          $taxProducts[$tax]['net_amount'] += $product['net_amount'];
          $taxProducts[$tax]['gross_amount'] += $product['gross_amount'];
        }
      }

      $products = $taxProducts;
    }

    $shippingData = $this->_getShippingProductData($order);

    if (null !== $shippingData) {

      $products[] = $shippingData;

      $itemsTax += $shippingData['gross_amount'] - $shippingData['net_amount'];
      $itemsNetPrice += $shippingData['net_amount'];
      $itemsGrossPrice += $shippingData['gross_amount'];

    }

    $discountAmount = $orderImpl->getTotalInclTax() - $itemsGrossPrice;
    if (abs($discountAmount) >= 1) {
      $discountProduct = $this->_getBasketDiscountData($orderImpl->getTotalInclTax(), $itemsGrossPrice,
        $orderImpl->getTotalExclTax(), $itemsNetPrice);

      $itemsTax -= abs($discountProduct['gross_amount'] - $discountProduct['net_amount']);
      $itemsNetPrice -= abs($discountProduct['net_amount']);
      $itemsGrossPrice -= abs($discountProduct['gross_amount']);

      $products[] = $discountProduct;

    }

    return $products;

  }

  protected function _getBasketItemData(OrderItemInterface $orderItem)
  {
    $itemCount = round($orderItem->getQuantity());

    $itemTax = 0;
    $itemTaxPercentage = 0;

    foreach ($orderItem->getAdjustments() as $adjustment) {
      if ($adjustment->getType() === 'tax') {
        $itemTax = $adjustment->getAmount()->getNumber();
        $itemTaxPercentage = $adjustment->getPercentage();
      }
    }

    $itemGross = round($orderItem->getUnitPrice()->getNumber(), 2);
    $itemTax = round($itemTax, 2);
    $itemNet = $itemGross - $itemTax;

    return [
      'name' => $orderItem->getTitle(),
      'unit_count' => (string)$itemCount,
      'unit_cost' => $itemNet * 100,
      'net_amount' => $itemNet * 100 * $itemCount,
      'gross_amount' => $itemGross * 100 * $itemCount,
      'tax_percentage' => $itemTaxPercentage * 10000,
      'discount_percentage' => 0
    ];
  }

  protected function _getShippingProductData(OrderInterface $order)
  {
    foreach ($order->collectAdjustments() as $adjustment) {
      if ($adjustment->getType() === 'shipping') {

        $itemNet = round($adjustment->getAmount()->getNumber(), 2);

        if ($itemNet <= 0) {
          return null;
        }

        return [
          'name' => $adjustment->getLabel(),
          'unit_count' => (string)1,
          'unit_cost' => $itemNet * 100,
          'net_amount' => $itemNet * 100,
          'gross_amount' => $itemNet * 100,
          'tax_percentage' => 0,
          'discount_percentage' => 0
        ];
      }
    }

    return null;
  }

  protected function _getBasketDiscountData($orderGrossAmount, $itemsGrossPrice, $orderNetAmount, $itemsNetPrice)
  {
// calculation for tax return for example 24%, so we have 24/100 => 0.24 but we need int value so *100.
    // But Nordea require round with precision = 2, so again we need *100. 100*100 = 10000
    if (round($orderNetAmount - $itemsNetPrice, 0)) {
      $tax = round(
          (round($orderGrossAmount - $itemsGrossPrice, 0) - round($orderNetAmount - $itemsNetPrice, 0)
          ) / round($orderNetAmount - $itemsNetPrice, 0), 2) * 10000;
    } else {
      $tax = 0;
    }

    return [
      'name' => 'Discount',
      'unit_count' => 1,
      'unit_cost' => round($orderNetAmount - $itemsNetPrice, 0),
      'net_amount' => round($orderNetAmount - $itemsNetPrice, 0),
      'gross_amount' => round($orderGrossAmount - $itemsGrossPrice, 0),
      'tax_percentage' => $tax,
      'discount_percentage' => 0
    ];
  }

  protected function _isSendBasketItems()
  {
    $configuration = $this->_getConfiguration();
    return $configuration['basket_item_sending'] === NordeaPayment::BASKET_ITEMS_SEND_FOR_ALL;
  }

  protected function _groupItemsByTax($items)
  {

    $result = [];

    foreach ($items as $orderItem) {
      $itemNet = round($orderItem['price'], 2);
      $itemTax = round($orderItem['tax'], 2);

      $tax = (string)round($this->_calculateTaxPercentage($itemNet, $itemTax), 2);

      if (!isset($result[$tax])) {

        $mergedItem = [];
        $mergedItem['name'] = sprintf('Multiple items - tax %s', $tax);
        $mergedItem['price'] = (0);
        $mergedItem['tax'] = (0);
        $mergedItem['quantity'] = 1;

        $result[$tax] = $mergedItem;
      }

      $result[$tax]['price'] = ((float)$result[$tax]['price'] + (float)$orderItem['price'] * $orderItem['quantity']);
      $result[$tax]['tax'] = ((float)$result[$tax]['tax'] + (float)$orderItem['tax']);
    }

    return $result;
  }

  protected function _calculateTaxPercentage($price, $tax)
  {
    return round($tax * 100 / $price);
  }


}