<?php

namespace Drupal\Tests\commerce_canadapost\Unit;

use Drupal\commerce_canadapost\Plugin\Commerce\ShippingMethod\CanadaPost;
use Drupal\commerce_canadapost\UtilitiesService;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\Plugin\Commerce\PackageType\PackageTypeInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\physical\Length;
use Drupal\physical\Weight;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\text\Plugin\Field\FieldType\TextLongItem;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Field\FieldItemListInterface;

use CommerceGuys\Addressing\AddressInterface;
use CommerceGuys\Addressing\Address;

define('COMMERCE_CANADAPOST_LOGGER_CHANNEL', 'commerce_canadapost');

/**
 * Class CanadaPostUnitTestBase.
 *
 * @package Drupal\Tests\commerce_canadapost\Unit
 */
abstract class CanadaPostUnitTestBase extends UnitTestCase {

  /**
   * The shipping method interface.
   *
   * @var \Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodInterface
   */
  protected $shippingMethod;

  /**
   * The shipment interface.
   *
   * @var \Drupal\commerce_shipping\Entity\ShipmentInterface
   */
  protected $shipment;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The utilities service class.
   *
   * @var \Drupal\commerce_canadapost\UtilitiesService
   */
  protected $utilities;

  /**
   * Set up requirements for test.
   */
  public function setUp() {
    parent::setUp();

    $this->shippingMethod = $this->mockShippingMethod();
    $this->shipment = $this->mockShipment();

    $logger_factory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $logger = $this->prophesize(LoggerChannelInterface::class);
    $logger_factory->get(COMMERCE_CANADAPOST_LOGGER_CHANNEL)
      ->willReturn($logger->reveal());
    $this->loggerFactory = $logger_factory->reveal();

    $utilities = $this->prophesize(UtilitiesService::class);
    $utilities
      ->getApiSettings(
        $this->shipment->getOrder()->getStore(),
        $this->shippingMethod
      )
      ->willReturn([]);
    $this->utilities = $utilities->reveal();
  }

  /**
   * Creates a mock Drupal Commerce shipment entity.
   *
   * @return \Drupal\commerce_shipping\Entity\ShipmentInterface
   *   A mocked commerce shipment object.
   */
  public function mockShipment() {
    // Mock a Drupal Commerce Order and associated objects.
    $order = $this->prophesize(OrderInterface::class);
    $store = $this->prophesize(StoreInterface::class);

    // Mock the store API settings.
    $api_settings = $this->prophesize(TextLongItem::class);
    $encoded_api_settings = json_encode($this->getStoreApiSettings());
    $api_settings->getValue()->willReturn([
      0 => [
        'value' => $encoded_api_settings,
      ],
    ]);
    $store->get('canadapost_api_settings')->willReturn($api_settings);

    // Mock the getAddress method to return a Canadian address.
    $store->getAddress()
      ->willReturn(new Address('CA', 'YK', 'Whitehorse', '', 'Y1A4P9', '', '9031 Quartz Road'));
    $order->getStore()->willReturn($store->reveal());

    // Mock a Drupal Commerce shipment and associated objects.
    $shipment = $this->prophesize(ShipmentInterface::class);
    $profile = $this->prophesize(ProfileInterface::class);
    $address_list = $this->prophesize(FieldItemListInterface::class);
    $address = $this->prophesize(AddressInterface::class);

    // Mock the address list to return a Canadian address.
    $address->getPostalCode()->willReturn('Y1A2C6');
    $address_list->first()->willReturn($address->reveal());
    $profile->get('address')->willReturn($address_list->reveal());
    $shipment->getShippingProfile()->willReturn($profile->reveal());
    $shipment->getOrder()->willReturn($order->reveal());
    $shipment->getShippingMethod()->willReturn($this->shippingMethod);

    // Mock the shipments weight.
    $shipment->getWeight()->willReturn(new Weight(1000, 'g'));

    // Return the mocked shipment object.
    return $shipment->reveal();
  }

  /**
   * Creates a mock Drupal Commerce shipping method.
   *
   * @return \Drupal\commerce_canadapost\Plugin\Commerce\ShippingMethod\CanadaPost
   *   The mocked shipping method.
   */
  public function mockShippingMethod() {
    $shipping_method = $this->prophesize(CanadaPost::class);
    $package_type = $this->prophesize(PackageTypeInterface::class);
    $package_type->getHeight()->willReturn(new Length(10, 'in'));
    $package_type->getLength()->willReturn(new Length(10, 'in'));
    $package_type->getWidth()->willReturn(new Length(3, 'in'));
    $package_type->getWeight()->willReturn(new Weight(10, 'lb'));
    $package_type->getRemoteId()->willReturn('custom');
    $shipping_method->apiIsConfigured()->willReturn(TRUE);
    $shipping_method->getDefaultPackageType()->willReturn($package_type);
    $shipping_method->getConfiguration()->willReturn([
      'api' => $this->getShipmentMethodApiSettings(),
      'shipping_information' => [
        'origin_postal_code' => 'V1X5V1',
      ],
    ]);

    return $shipping_method->reveal();
  }

  /**
   * Returns an array of mock Canada Post API settings.
   *
   * @return array
   *   The API settings.
   */
  protected function getShipmentMethodApiSettings() {
    return [
      'customer_number' => 'shipment_method_mock_cn',
      'username' => 'shipment_method_mock_name',
      'password' => 'shipment_method_mock_pwd',
      'contract_id' => '',
      'rate.origin_postal_code' => '',
      'mode' => 'test',
      'log' => [
        'request' => FALSE,
        'response' => FALSE,
      ],
    ];
  }

  /**
   * Returns an array of mock Canada Post API settings.
   *
   * @return array
   *   The API settings.
   */
  protected function getStoreApiSettings() {
    return [
      'customer_number' => 'store_mock_cn',
      'username' => 'store_mock_name',
      'password' => 'store_mock_pwd',
      'contract_id' => '',
      'rate.origin_postal_code' => '',
      'mode' => 'live',
      'log' => [
        'request' => FALSE,
        'response' => FALSE,
      ],
    ];
  }

}
