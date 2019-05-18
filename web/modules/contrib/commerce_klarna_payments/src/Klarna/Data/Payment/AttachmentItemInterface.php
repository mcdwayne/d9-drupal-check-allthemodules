<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Data\Payment;

use Drupal\commerce_klarna_payments\Klarna\Data\ObjectInterface;

/**
 * An interface to describe attachments.
 *
 * @todo Add setters for this.
 */
interface AttachmentItemInterface extends ObjectInterface {

  /**
   * Details about the reservation of airline tickets.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\AirReservationTransportInterface[] $details
   *   The details.
   *
   * @return $this
   *   The self.
   */
  public function setAirReservationDetails(array $details) : AttachmentItemInterface;

  /**
   * Adds a single reservation.
   *
   * Details about the reservation of airline tickets.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\AirReservationTransportInterface $details
   *   The details.
   *
   * @return $this
   *   The self.
   */
  public function addAirReservationDetails(AirReservationTransportInterface $details) : AttachmentItemInterface;

  /**
   * Details about the reservation of bus tickets.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\BusReservationTransportInterface[] $details
   *   The details.
   *
   * @return $this
   *   The self.
   */
  public function setBusReservationDetails(array $details) : AttachmentItemInterface;

  /**
   * Details about the reservation of bus tickets.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\BusReservationTransportInterface $details
   *   The details.
   *
   * @return $this
   *   The self.
   */
  public function addBusReservationDetails(BusReservationTransportInterface $details) : AttachmentItemInterface;

  /**
   * Details about the reservation of train tickets.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\TrainReservationTransportInterface[] $details
   *   The details.
   *
   * @return $this
   *   The self.
   */
  public function setTrainReservationDetails(array $details) : AttachmentItemInterface;

  /**
   * Details about the reservation of train tickets.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\TrainReservationTransportInterface $details
   *   The details.
   *
   * @return $this
   *   The self.
   */
  public function addTrainReservationDetails(TrainReservationTransportInterface $details) : AttachmentItemInterface;

  /**
   * Details about the reservation of ferry tickets.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\FerryReservationTransportInterface[] $details
   *   The details.
   *
   * @return $this
   *   The self.
   */
  public function setFerryReservationDetails(array $details) : AttachmentItemInterface;

  /**
   * Details about the reservation of ferry tickets.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\FerryReservationTransportInterface $details
   *   The details.
   *
   * @return $this
   *   The self.
   */
  public function addFerryReservationDetails(FerryReservationTransportInterface $details) : AttachmentItemInterface;

  /**
   * Details about the reservation of hotel rooms.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\HotelReservationInterface[] $details
   *   The details.
   *
   * @return $this
   *   The self.
   */
  public function setHotelReservationDetails(array $details) : AttachmentItemInterface;

  /**
   * Details about the reservation of hotel rooms.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\HotelReservationInterface $details
   *   The details.
   *
   * @return $this
   *   The self.
   */
  public function addHotelReservationDetails(HotelReservationInterface $details) : AttachmentItemInterface;

  /**
   * Details about the reservation of rental cars.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\CarRentalReservationInterface[] $details
   *   The details.
   *
   * @return $this
   *   The self.
   */
  public function setCarRentalReservationDetails(array $details) : AttachmentItemInterface;

  /**
   * Details about the reservation of rental cars.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\CarRentalReservationInterface $details
   *   The details.
   *
   * @return $this
   *   The self.
   */
  public function addCarRentalReservationDetails(CarRentalReservationInterface $details) : AttachmentItemInterface;

  /**
   * Details about the events.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\EventInterface[] $events
   *   The events.
   *
   * @return $this
   *   The self.
   */
  public function setEvents(array $events) : AttachmentItemInterface;

  /**
   * Details about the event.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\EventInterface $event
   *   The event.
   *
   * @return $this
   *   The self.
   */
  public function addEvent(EventInterface $event) : AttachmentItemInterface;

  /**
   * Details about the voucher.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\VoucherInterface[] $vouchers
   *   The vouchers.
   *
   * @return $this
   *   The self.
   */
  public function setVouchers(array $vouchers) : AttachmentItemInterface;

  /**
   * Details about the voucher.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\VoucherInterface $voucher
   *   The voucher.
   *
   * @return $this
   *   The self.
   */
  public function addVoucher(VoucherInterface $voucher) : AttachmentItemInterface;

  /**
   * Details about the subscriptions.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\SubscriptionInterface[] $subscriptions
   *   The subscriptions.
   *
   * @return $this
   *   The self.
   */
  public function setSubscriptions(array $subscriptions) : AttachmentItemInterface;

  /**
   * Details about the subscription.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\SubscriptionInterface $subscription
   *   The subscriptions.
   *
   * @return $this
   *   The self.
   */
  public function addSubscription(SubscriptionInterface $subscription) : AttachmentItemInterface;

}
