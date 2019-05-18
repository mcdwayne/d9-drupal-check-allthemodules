<?php

namespace Drupal\mortgage_calculator;

/**
 * MortgageCalculatorCalculation.
 */
class MortgageCalculatorCalculation {

  private $loanAmount;
  private $mortgageRate;
  private $yearsToPay;
  private $desiredDisplay;

  /**
   * Construct.
   *
   * @Param int $loan_amount
   *   - a loan amount
   * @Param int $mortgage_rate
   *   - a mortgage rate
   * @Param int $years_to_pay
   *   - years to pay
   * @Param string $desired_display
   *   - possible values 'monthly' or 'yearly'
   */
  public function __construct($loan_amount, $mortgage_rate, $years_to_pay, $desired_display) {
    $this->loanAmount = $loan_amount;
    $this->mortgageRate = $mortgage_rate;
    $this->yearsToPay = $years_to_pay;
    $this->desiredDisplay = $desired_display;
  }

  /**
   * Calculation function.
   *
   * @return array
   *   - a key 'rows' contains array of rows with $desired_display mortgage
   *     calculations
   *   - a key 'number_of_payments' - a number of payments
   *   - a key 'payment' - an amount of payments
   */
  public function calculate() {
    if ($this->desiredDisplay == 'monthly') {
      $rate_per = ($this->mortgageRate / 100) / 12;
      $number_of_payments = $this->yearsToPay * 12;
    }
    else {
      $rate_per = $this->mortgageRate / 100;
      $number_of_payments = $this->yearsToPay;
    }

    if ($this->mortgageRate != 0) {
      $payment = ($this->loanAmount * pow(1 + $rate_per, $number_of_payments) * $rate_per) / (pow(1 + $rate_per, $number_of_payments) - 1);
    }
    else {
      $payment = $this->loanAmount / $number_of_payments;
    }

    $rows = [];
    $beginning_balance = $this->loanAmount;
    for ($i = 1; $i <= $number_of_payments; $i++) {
      $interest = $rate_per * $beginning_balance;
      $rows[] = [
        $i,
        round($beginning_balance),
        round($interest),
        round($payment),
        abs(round($beginning_balance - ($payment - $interest))),
      ];
      $beginning_balance -= $payment - $interest;
    }

    return [
      'rows' => $rows,
      'row' => [
        'number_of_payments' => $number_of_payments,
        'payment' => round($payment),
      ],
    ];
  }

}
