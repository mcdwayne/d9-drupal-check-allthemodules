/**
 * @file
 *
 * Implement a HomeLoan calculator.
 *
 */
(function ($) {

  // Make sure our objects are defined.
  Drupal.HomeLoanCalculator = Drupal.HomeLoanCalculator || {};

  /**
   * Calculation after a click
   */
  Drupal.HomeLoanCalculator.cmdCalc_Click = function(form) {
    if (form.loan_amount.value == 0 || form.loan_amount.value.length == 0) {
      alert (Drupal.t("The Price field can't be 0!"));
      form.loan_amount.focus();
    }
    else if (form.loan_interest.value == 0 || form.loan_interest.value.length == 0) {
      alert (Drupal.t("The Interest Rate field can't be 0!"));
      form.loan_interest.focus();
    }
    else if (form.loan_length.value == 0 || form.loan_length.value.length == 0) {
      alert (Drupal.t("The Loan Length field can't be 0!"));
      form.loan_length.focus();
    }
    else {
      results = Drupal.HomeLoanCalculator.homeloanCalculation(form.loan_amount.value, form.loan_interest.value, form.loan_length.value, 0);
      form.result_2.value = Math.round(results['calculation']);
    }
  }

  /**
   * HomeLoan calculation
   */
  Drupal.HomeLoanCalculator.homeloanCalculation = function(loanAmount, interestRate, yearsToPay, downPayment) {
    amount = loanAmount;
    tenature = yearsToPay;
    intRate = interestRate / 1200;
    results = new Array();
    results['calculation'] = amount * intRate / (1 - (Math.pow(1 / (1 + intRate), tenature)));
    results['princ'] = loanAmount;
    results['months'] = yearsToPay;
    return results;
  }

  Drupal.behaviors.homeCalculator = {
    attach: function (context, settings) {
      $('[id*="edit-calculate-2"]',context).click(function (event) {
       Drupal.HomeLoanCalculator.cmdCalc_Click(this.form);
       event.preventDefault();
       event.stopPropagation();
       return false;
    });
    }
  };
})(jQuery);
