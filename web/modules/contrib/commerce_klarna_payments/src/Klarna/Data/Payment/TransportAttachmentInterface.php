<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Data\Payment;

use Drupal\commerce_klarna_payments\Klarna\Data\ObjectInterface;

/**
 * An interface to describe generic transport related methods.
 */
interface TransportAttachmentInterface extends ObjectInterface {

  /**
   * Sets the PNR.
   *
   * Trip booking number, e.g. VH67899
   *
   * @param string $pnr
   *   The PNR.
   *
   * @return $this
   *   The self.
   */
  public function setPnr(string $pnr) : TransportAttachmentInterface;

  /**
   * Sets the itinerary.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\ItineraryInterface $itinerary
   *   The itinerary.
   *
   * @return $this
   *   The self.
   */
  public function addItinerary(ItineraryInterface $itinerary) : TransportAttachmentInterface;

  /**
   * Sets the itineraries.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\ItineraryInterface[] $itineraries
   *   An array of itineraries objects.
   *
   * @return $this
   *   The self.
   */
  public function setItineraries(array $itineraries) : TransportAttachmentInterface;

  /**
   * Sets the insurances.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\InsuranceInterface[] $insurances
   *   An array of insurances.
   *
   * @return $this
   *   The self.
   */
  public function setInsurances(array $insurances) : TransportAttachmentInterface;

  /**
   * Adds a single insurance.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\InsuranceInterface $insurance
   *   The insurance.
   *
   * @return $this
   *   The self.
   */
  public function addInsurance(InsuranceInterface $insurance) : TransportAttachmentInterface;

  /**
   * Sets the passengers.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\PassengerInterface[] $passengers
   *   The passengers.
   *
   * @return $this
   *   The self.
   */
  public function setPassengers(array $passengers) : TransportAttachmentInterface;

  /**
   * Adds a single passenger.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\PassengerInterface $passenger
   *   The passenger.
   *
   * @return $this
   *   The self.
   */
  public function addPassenger(PassengerInterface $passenger) : TransportAttachmentInterface;

  /**
   * Sets the affiliate.
   *
   * @param string $affiliate
   *   The affiliate.
   *
   * @return $this
   *   The self.
   */
  public function setAffiliateName(string $affiliate) : TransportAttachmentInterface;

}
