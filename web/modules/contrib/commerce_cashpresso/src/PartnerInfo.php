<?php

namespace Drupal\commerce_cashpresso;

use Drupal\commerce_price\Calculator;
use Drupal\commerce_price\Price;

/**
 * Provides a value object for storing data from the Get Partner Info request.
 */
final class PartnerInfo {

  /**
   * The pending status of partner accounts.
   *
   * @var string
   */
  const STATUS_PENDING = 'PENDING';

  /**
   * The active status of partner accounts.
   *
   * @var string
   */
  const STATUS_ACTIVE = 'ACTIVE';

  /**
   * The declined status of partner accounts.
   *
   * @var string
   */
  const STATUS_DECLINED = 'DECLINED';

  /**
   * The partner's brand name.
   *
   * The shop or otherwise customer facing name. cashpresso will use this name
   * in all communication towards your customers.
   *
   * @var string
   */
  protected $brandName;

  /**
   * The legal company name.
   *
   * @var string
   */
  protected $companyName;

  /**
   * The company website url.
   *
   * @var string
   */
  protected $companyUrl;

  /**
   * The partner's email address.
   *
   * @var string
   */
  protected $email;

  /**
   * The holder's name.
   *
   * @var string
   */
  protected $holder;

  /**
   * The IBAN.
   *
   * @var string
   */
  protected $iban;

  /**
   * Whether the partner is allowed to set interest per purchase.
   *
   * @var bool
   */
  protected $interestFreeEnabled;

  /**
   * The maximum number of interest free days the partner is allowed to offer.
   *
   * @var int
   */
  protected $interestFreeMaxDuration;

  /**
   * The status of the partner account. One of PENDING, ACTIVE or DECLINED.
   *
   * Please note that the partner can only send in payment requests if the
   * account is in state ACTIVE.
   *
   * @var string
   */
  protected $status;

  /**
   * Three letter ISO currency code, e.g. EUR.
   *
   * @var string
   */
  protected $currencyCode;

  /**
   * The minimum payback amount.
   *
   * The minimum amount a customer has to pay per month in the respective
   * currency value. cashpresso payback terms usually come with a paybackRate
   * in percent and a minimum amount.
   *
   * @var float
   */
  protected $minPaybackAmount;

  /**
   * The payback rate in percent.
   *
   * You can always calculate the first instalment of a product with price P
   * using this formula: min(P, max(minPaybackAmount, P * 0.01 * paybackRate)).
   *
   * If you have a product with price 750€, paybackRate is 3.00 and
   * minPaybackAmount is 20€, the first instalment is:
   *   min(750, max(20, 750*0.01*3.00)) = 22.5
   *
   * If you have a product with price 500€ and the same terms, the first
   * instalment is:
   *   min(500, max(20, 500*0.01*3.00)) = 20
   *
   * @var float
   */
  protected $paybackRate;

  /**
   * The highest amount new customers can finance with cashpresso.
   *
   * @var \Drupal\commerce_price\Price
   *   The financing limit.
   */
  protected $financingLimit;

  /**
   * The highest amount for prepayment with cashpresso.
   *
   * @var \Drupal\commerce_price\Price
   *   The prepayment limit.
   */
  protected $prepaymentLimit;

  /**
   * The total limit.
   *
   * @var \Drupal\commerce_price\Price
   *   The total limit.
   */
  protected $totalLimit;

  /**
   * The minimum nominal interest rate in percent.
   *
   * @var float
   */
  protected $minNominalInterestRate;

  /**
   * The maximum nominal interest rate in percent.
   *
   * @var float
   */
  protected $maxNominalInterestRate;

  /**
   * The minimum effective interest rate in percent.
   *
   * @var float
   */
  protected $minEffectiveInterestRate;

  /**
   * The maximum effective interest rate in percent.
   *
   * @var float
   */
  protected $maxEffectiveInterestRate;

  /**
   * Interest free days granted by cashpresso.
   *
   * The number of interest free days cashpresso grants per purchase. Any amount
   * of interest free days the partner grants will be added on top of this.
   *
   * @var int
   */
  protected $interestFreeCashpresso;

  /**
   * Factory method allowing the object to be instantiated by an array.
   *
   * @param array $values
   *   The values, as returned by Get Partner Info webservice.
   *
   * @return static
   *   A new PartnerInfo object.
   */
  public static function fromArray(array $values) {
    $instance = new PartnerInfo();
    $instance->setBrandName(isset($values['brandName']) ? $values['brandName'] : '');
    $instance->setCompanyName(isset($values['companyName']) ? $values['companyName'] : '');
    $instance->setCompanyUrl(isset($values['companyUrl']) ? $values['companyUrl'] : '');
    $instance->setEmail(isset($values['email']) ? $values['email'] : '');
    $instance->setHolder(isset($values['holder']) ? $values['holder'] : '');
    $instance->setIban(isset($values['iban']) ? $values['iban'] : '');
    $instance->setInterestFreeEnabled(isset($values['interestFreeEnabled']) ? $values['interestFreeEnabled'] : FALSE);
    $instance->setInterestFreeMaxDuration(isset($values['interestFreeMaxDuration']) ? $values['interestFreeMaxDuration'] : 0);
    $instance->setStatus(isset($values['status']) ? $values['status'] : self::STATUS_PENDING);
    $instance->setCurrencyCode($values['currency'] ? $values['currency'] : 'EUR');
    $instance->setMinPaybackAmount($values['minPaybackAmount']);
    $instance->setPaybackRate($values['paybackRate']);
    if (!empty($values['limit']['financing'])) {
      $instance->setFinancingLimit(new Price((string) $values['limit']['financing'], $values['currency']));
    }
    if (!empty($values['limit']['prepayment'])) {
      $instance->setPrepaymentLimit(new Price((string) $values['limit']['prepayment'], $values['currency']));
    }
    if (!empty($values['limit']['total'])) {
      $instance->setTotalLimit(new Price((string) $values['limit']['total'], $values['currency']));
    }
    $instance->setMinNominalInterestRate($values['interest']['nominal']['min']);
    $instance->setMaxNominalInterestRate($values['interest']['nominal']['max']);
    $instance->setMinEffectiveInterestRate($values['interest']['effective']['min']);
    $instance->setMaxEffectiveInterestRate($values['interest']['effective']['max']);
    $instance->setInterestFreeCashpresso($values['interestFreeCashpresso']);
    return $instance;
  }

  /**
   * Return the brand name.
   *
   * @return string
   *   The brand name.
   */
  public function getBrandName() {
    return $this->brandName;
  }

  /**
   * Set the brand name.
   *
   * @param string $brand_name
   *   The brand name.
   *
   * @return $this
   */
  public function setBrandName($brand_name) {
    $this->brandName = $brand_name;
    return $this;
  }

  /**
   * Get the company name.
   *
   * @return string
   *   The company name.
   */
  public function getCompanyName() {
    return $this->companyName;
  }

  /**
   * Set the company name.
   *
   * @param string $company_name
   *   The company name.
   *
   * @return $this
   */
  public function setCompanyName($company_name) {
    $this->companyName = $company_name;
    return $this;
  }

  /**
   * Get the company url.
   *
   * @return string
   *   The company url.
   */
  public function getCompanyUrl() {
    return $this->companyUrl;
  }

  /**
   * Set the company url.
   *
   * @param string $company_url
   *   The company url.
   *
   * @return $this
   */
  public function setCompanyUrl($company_url) {
    $this->companyUrl = $company_url;
    return $this;
  }

  /**
   * Get the email address.
   *
   * @return string
   *   The email address.
   */
  public function getEmail() {
    return $this->email;
  }

  /**
   * Set the email address.
   *
   * @param string $email
   *   The email address.
   *
   * @return $this
   */
  public function setEmail($email) {
    $this->email = $email;
    return $this;
  }

  /**
   * Gets the holder.
   *
   * @return string
   *   The holder.
   */
  public function getHolder() {
    return $this->holder;
  }

  /**
   * Sets the holder.
   *
   * @param string $holder
   *   The holder.
   *
   * @return $this
   */
  public function setHolder($holder) {
    $this->holder = $holder;
    return $this;
  }

  /**
   * Gets the IBAN.
   *
   * @return string
   *   The IBAN.
   */
  public function getIban() {
    return $this->iban;
  }

  /**
   * Sets the IBAN.
   *
   * @param string $iban
   *   The IBAN.
   *
   * @return $this
   */
  public function setIban($iban) {
    $this->iban = $iban;
    return $this;
  }

  /**
   * Gets whether interest free is enabled.
   *
   * @return bool
   *   TRUE, if interest free is enabled. FALSE otherwise.
   */
  public function isInterestFreeEnabled() {
    return $this->interestFreeEnabled;
  }

  /**
   * Sets whether interest free is enabled.
   *
   * @param bool $interestFreeEnabled
   *   TRUE, if interest free should be enabled. FALSE otherwise.
   *
   * @return $this
   */
  public function setInterestFreeEnabled($interestFreeEnabled) {
    $this->interestFreeEnabled = (bool) $interestFreeEnabled;
    return $this;
  }

  /**
   * Returns the max interest free duration.
   *
   * @return int
   *   The max interest free duration.
   */
  public function getInterestFreeMaxDuration() {
    return $this->interestFreeMaxDuration;
  }

  /**
   * Sets the max interest free duration.
   *
   * @param int $interest_free_max_duration
   *   The max interest free duration.
   *
   * @return $this
   */
  public function setInterestFreeMaxDuration($interest_free_max_duration) {
    $this->interestFreeMaxDuration = (int) $interest_free_max_duration;
    return $this;
  }

  /**
   * Gets the partner account status.
   *
   * @return string
   *   The partner account status.
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * Sets the partner account status.
   *
   * @param string $status
   *   The partner account status.
   *
   * @return $this
   */
  public function setStatus($status) {
    $this->status = $status;
    return $this;
  }

  /**
   * Gets the currency code.
   *
   * @return string
   *   The currency code.
   */
  public function getCurrencyCode() {
    return $this->currencyCode;
  }

  /**
   * Sets the currency code.
   *
   * @param string $currency_code
   *   The currency code.
   *
   * @return $this
   */
  public function setCurrencyCode($currency_code) {
    $this->currencyCode = $currency_code;
    return $this;
  }

  /**
   * Gets the minimum payback amount.
   *
   * @return float
   *   The minimum payback amount.
   */
  public function getMinPaybackAmount() {
    return $this->minPaybackAmount;
  }

  /**
   * Sets the minimum payback amount.
   *
   * @param float $minPaybackAmount
   *   The minimum payback amount.
   *
   * @return $this
   */
  public function setMinPaybackAmount($minPaybackAmount) {
    $this->minPaybackAmount = $minPaybackAmount;
    return $this;
  }

  /**
   * Gets the payback rate.
   *
   * @return float
   *   The payback rate.
   */
  public function getPaybackRate() {
    return $this->paybackRate;
  }

  /**
   * Sets the payback rate.
   *
   * @param float $paybackRate
   *   The payback rate.
   *
   * @return $this
   */
  public function setPaybackRate($paybackRate) {
    $this->paybackRate = $paybackRate;
    return $this;
  }

  /**
   * Gets the financing limit.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The financing limit.
   */
  public function getFinancingLimit() {
    return $this->financingLimit;
  }

  /**
   * Sets the financing limit.
   *
   * @param \Drupal\commerce_price\Price $financing_limit
   *   The financing limit.
   *
   * @return $this
   */
  public function setFinancingLimit(Price $financing_limit) {
    $this->financingLimit = $financing_limit;
    return $this;
  }

  /**
   * Sets the prepayment limit.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The prepayment limit.
   */
  public function getPrepaymentLimit() {
    return $this->prepaymentLimit;
  }

  /**
   * Sets the prepayment limit.
   *
   * @param \Drupal\commerce_price\Price $prepayment_limit
   *   The prepayment limit.
   *
   * @return $this
   */
  public function setPrepaymentLimit(Price $prepayment_limit) {
    $this->prepaymentLimit = $prepayment_limit;
    return $this;
  }

  /**
   * Gets the total limit.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The total limit.
   */
  public function getTotalLimit() {
    return $this->totalLimit;
  }

  /**
   * Sets the total limit.
   *
   * @param \Drupal\commerce_price\Price $total_limit
   *   The total limit.
   *
   * @return $this
   */
  public function setTotalLimit(Price $total_limit) {
    $this->totalLimit = $total_limit;
    return $this;
  }

  /**
   * Gets the minimum nominal interest rate.
   *
   * @return float
   *   The minimum nominal interest rate.
   */
  public function getMinNominalInterestRate() {
    return $this->minNominalInterestRate;
  }

  /**
   * Sets the minimum nominal interest rate.
   *
   * @param float $rate
   *   The minimum nominal interest rate.
   *
   * @return $this
   */
  public function setMinNominalInterestRate($rate) {
    $this->minNominalInterestRate = $rate;
    return $this;
  }

  /**
   * Gets the maximum nominal interest rate.
   *
   * @return float
   *   The maximum nominal interest rate.
   */
  public function getMaxNominalInterestRate() {
    return $this->maxNominalInterestRate;
  }

  /**
   * Sets the maximum nominal interest rate.
   *
   * @param float $rate
   *   The maximum nominal interest rate.
   *
   * @return $this
   */
  public function setMaxNominalInterestRate($rate) {
    $this->maxNominalInterestRate = $rate;
    return $this;
  }

  /**
   * Gets the minimum effective interest rate.
   *
   * @return float
   *   The minimum effective interest rate.
   */
  public function getMinEffectiveInterestRate() {
    return $this->minEffectiveInterestRate;
  }

  /**
   * Sets the minimum effective interest rate.
   *
   * @param float $rate
   *   The minimum effective interest rate.
   *
   * @return $this
   */
  public function setMinEffectiveInterestRate($rate) {
    $this->minEffectiveInterestRate = $rate;
    return $this;
  }

  /**
   * Gets the maximum effective interest rate.
   *
   * @return float
   *   The maximum effective interest rate.
   */
  public function getMaxEffectiveInterestRate() {
    return $this->maxEffectiveInterestRate;
  }

  /**
   * Sets the maximum effective interest rate.
   *
   * @param float $rate
   *   The maximum effective interest rate.
   *
   * @return $this
   */
  public function setMaxEffectiveInterestRate($rate) {
    $this->maxEffectiveInterestRate = $rate;
    return $this;
  }

  /**
   * Gets the number of interest free days cashpresso grants per purchase.
   *
   * @return int
   *   The number of interest free days cashpresso grants per purchase.
   */
  public function getInterestFreeCashpresso() {
    return $this->interestFreeCashpresso;
  }

  /**
   * Sets the number of interest free days cashpresso grants per purchase.
   *
   * @param int $interest_free_cashpresso
   *   The number of interest free days cashpresso grants per purchase.
   *
   * @return $this
   */
  public function setInterestFreeCashpresso($interest_free_cashpresso) {
    $this->interestFreeCashpresso = (int) $interest_free_cashpresso;
    return $this;
  }

  /**
   * Calculates the instalment price for the given product price.
   *
   * The formula for product price P is:
   *   min(P, max(minPaybackAmount, P * 0.01 * paybackRate))
   *
   * @param \Drupal\commerce_price\Price $product_price
   *   The product price.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The instalment price, or NULL, if the partner info is incomplete (which
   *   should never really be possible in fact).
   */
  public function calculateInstalmentPrice(Price $product_price) {
    $min_payback_amount = (string) $this->getMinPaybackAmount();
    $payback_rate = (string) $this->getPaybackRate();
    if (empty($min_payback_amount) || empty($payback_rate)) {
      return NULL;
    }
    $price_amount = $product_price->getNumber();
    // Normalize percentage number to a rate decimal string.
    $payback_rate = Calculator::multiply($payback_rate, '0.01');
    // Calculate the payback amount for the given price.
    $payback_amount = Calculator::multiply($price_amount, $payback_rate);
    // Ensure the minimum payback amount.
    if (Calculator::compare($min_payback_amount, $payback_amount) > 0) {
      $payback_amount = $min_payback_amount;
    }
    // Ensure that the payback amount is not higher than the product price.
    if (Calculator::compare($payback_amount, $price_amount) > 0) {
      $payback_amount = $price_amount;
    }
    return new Price($payback_amount, $product_price->getCurrencyCode());
  }

}
