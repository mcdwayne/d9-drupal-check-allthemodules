/**
 * @file
 *
 * Implement a loan calculator.
 *
 */
(function ($) {

  // Make sure our objects are defined.
  Drupal.LCalculator = Drupal.LCalculator || {};

  /**
   * Calculation after a click
   */
  Drupal.LCalculator.cmdCalc_Click = function(form) {
    if (form.loan_amount_2.value == 0 || form.loan_amount_2.value.length == 0) {
      alert (Drupal.t("The Principle field can't be 0!"));
      form.loan_amount_2.focus();
    }
    else if (form.loan_rate_2.value == 0 || form.loan_rate_2.value.length == 0) {
      alert (Drupal.t("The Interest Rate field can't be 0!"));
      form.loan_rate_2.focus();
    }
    else if (form.years_to_pay_2.value == 0 || form.years_to_pay_2.value.length == 0) {
      alert (Drupal.t("Number of Years field can't be 0!"));
      form.years_to_pay_2.focus();
    }
    else {
      results = Drupal.LCalculator.loanCalculation(form.loan_amount_2.value, form.loan_rate_2.value, form.years_to_pay_2.value);
      form.result_1.value = results['calculation'];
    }
  }
  Drupal.LCalculator.loanCalculation = function(loanAmount, loanRate, yearsToPay) {
    intRate = (loanRate)/1200;
    months = yearsToPay * 12;
    princ = loanAmount * intRate * ((Math.pow((1+intRate),months))/(Math.pow((1+intRate),months) - 1));
    results = new Array();
   results['calculation'] = princ;
    return results;
  }

  Drupal.behaviors.loanCalculator = {
    attach: function (context, settings) {
      $("#loan-calculator-js-form input.button", context).click(function(event) {
        Drupal.LCalculator.cmdCalc_Click(this.form);
        return false;
      });
    }
  };
})(jQuery);
