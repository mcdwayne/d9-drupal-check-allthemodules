<?php

namespace Drupal\commerce_adyen\Adyen\Composition;

/**
 * Shopper information for OpenInvoice payment.
 */
class Shopper {

  /**
   * Infix.
   *
   * @var string
   */
  private $infix = '';
  /**
   * Shopper gender.
   *
   * @var string
   */
  private $gender = '';
  /**
   * Shopper last name.
   *
   * @var string
   */
  private $lastName = '';
  /**
   * Shopper first name.
   *
   * @var string
   */
  private $firstName = '';
  /**
   * Shopper telephone number.
   *
   * @var string
   */
  private $telephoneNumber = '';
  /**
   * Shopper year of birth.
   *
   * @var string
   */
  private $dateOfBirthYear = '';
  /**
   * Shopper month of birth.
   *
   * @var string
   */
  private $dateOfBirthMonth = '';
  /**
   * Shopper day of birth.
   *
   * @var string
   */
  private $dateOfBirthDayOfMonth = '';
  /**
   * Shopper social security number.
   *
   * @var string
   */
  private $socialSecurityNumber = '';

  /**
   * {@inheritdoc}
   */
  public function getInfix() {
    return $this->infix;
  }

  /**
   * {@inheritdoc}
   */
  public function setInfix($infix) {
    $this->infix = $infix;
  }

  /**
   * {@inheritdoc}
   */
  public function getGender() {
    return $this->gender;
  }

  /**
   * {@inheritdoc}
   */
  public function setGender($gender) {
    $this->gender = $gender;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastName() {
    return $this->lastName;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastName($last_name) {
    $this->lastName = $last_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstName() {
    return $this->firstName;
  }

  /**
   * {@inheritdoc}
   */
  public function setFirstName($first_name) {
    $this->firstName = $first_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getTelephoneNumber() {
    return $this->telephoneNumber;
  }

  /**
   * {@inheritdoc}
   */
  public function setTelephoneNumber($telephone_number) {
    $this->telephoneNumber = $telephone_number;
  }

  /**
   * {@inheritdoc}
   */
  public function getDateOfBirthYear() {
    return $this->dateOfBirthYear;
  }

  /**
   * {@inheritdoc}
   */
  public function setDateOfBirthYear($date_of_birth_year) {
    $this->dateOfBirthYear = $date_of_birth_year;
  }

  /**
   * {@inheritdoc}
   */
  public function getDateOfBirthMonth() {
    return $this->dateOfBirthMonth;
  }

  /**
   * {@inheritdoc}
   */
  public function setDateOfBirthMonth($date_of_birth_month) {
    $this->dateOfBirthMonth = $date_of_birth_month;
  }

  /**
   * {@inheritdoc}
   */
  public function getDateOfBirthDayOfMonth() {
    return $this->dateOfBirthDayOfMonth;
  }

  /**
   * {@inheritdoc}
   */
  public function setDateOfBirthDayOfMonth($date_of_birth_day_of_month) {
    $this->dateOfBirthDayOfMonth = $date_of_birth_day_of_month;
  }

  /**
   * {@inheritdoc}
   */
  public function setSocialSecurityNumber($social_security_number) {
    $this->socialSecurityNumber = $social_security_number;
  }

  /**
   * {@inheritdoc}
   */
  public function getSocialSecurityNumber() {
    return $this->socialSecurityNumber;
  }

}
