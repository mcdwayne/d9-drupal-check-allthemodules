<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Request\Payment;

use Drupal\commerce_klarna_payments\Klarna\Data\Payment\AirReservationTransportInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\Payment\AttachmentItemInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\Payment\BusReservationTransportInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\Payment\CarRentalReservationInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\Payment\EventInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\Payment\FerryReservationTransportInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\Payment\HotelReservationInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\Payment\SubscriptionInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\Payment\TrainReservationTransportInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\Payment\VoucherInterface;
use Drupal\commerce_klarna_payments\Klarna\RequestBase;
use Webmozart\Assert\Assert;

/**
 * Value object for attachment item.
 */
class AttachmentItem extends RequestBase implements AttachmentItemInterface {

  protected $data = [];

  /**
   * {@inheritdoc}
   */
  public function setAirReservationDetails(array $details) : AttachmentItemInterface {
    Assert::allIsInstanceOf($details, AirReservationTransportInterface::class);

    $this->data['air_reservation_details'] = $details;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addAirReservationDetails(AirReservationTransportInterface $details) : AttachmentItemInterface {
    $this->data['air_reservation_details'][] = $details;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setBusReservationDetails(array $details) : AttachmentItemInterface {
    Assert::allIsInstanceOf($details, BusReservationTransportInterface::class);

    $this->data['bus_reservation_details'] = $details;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addBusReservationDetails(BusReservationTransportInterface $details) : AttachmentItemInterface {
    $this->data['bus_reservation_details'][] = $details;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTrainReservationDetails(array $details) : AttachmentItemInterface {
    Assert::allIsInstanceOf($details, TrainReservationTransportInterface::class);

    $this->data['train_reservation_details'] = $details;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addTrainReservationDetails(TrainReservationTransportInterface $details) : AttachmentItemInterface {
    $this->data['train_reservation_details'][] = $details;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setFerryReservationDetails(array $details) : AttachmentItemInterface {
    Assert::allIsInstanceOf($details, FerryReservationTransportInterface::class);

    $this->data['ferry_reservation_details'] = $details;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addFerryReservationDetails(FerryReservationTransportInterface $details) : AttachmentItemInterface {
    $this->data['ferry_reservation_details'][] = $details;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setHotelReservationDetails(array $details) : AttachmentItemInterface {
    Assert::allIsInstanceOf($details, HotelReservationInterface::class);

    $this->data['hotel_reservation_details'] = $details;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addHotelReservationDetails(HotelReservationInterface $details) : AttachmentItemInterface {
    $this->data['hotel_reservation_details'][] = $details;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCarRentalReservationDetails(array $details) : AttachmentItemInterface {
    Assert::allIsInstanceOf($details, CarRentalReservationInterface::class);

    $this->data['car_rental_reservation_details'] = $details;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addCarRentalReservationDetails(CarRentalReservationInterface $details) : AttachmentItemInterface {
    $this->data['car_rental_reservation_details'][] = $details;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setEvents(array $events) : AttachmentItemInterface {
    Assert::allIsInstanceOf($events, EventInterface::class);

    $this->data['event'] = $events;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addEvent(EventInterface $event) : AttachmentItemInterface {
    $this->data['event'][] = $event;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setVouchers(array $vouchers) : AttachmentItemInterface {
    Assert::allIsInstanceOf($vouchers, VoucherInterface::class);

    $this->data['voucher'] = $vouchers;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addVoucher(VoucherInterface $voucher) : AttachmentItemInterface {
    $this->data['voucher'][] = $voucher;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubscriptions(array $subscriptions) : AttachmentItemInterface {
    Assert::allIsInstanceOf($subscriptions, SubscriptionInterface::class);

    $this->data['subscription'] = $subscriptions;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addSubscription(SubscriptionInterface $subscription) : AttachmentItemInterface {
    $this->data['subscription'][] = $subscription;

    return $this;
  }

}
