/**
 * @file
 *
 * Implement a compound calculator.
 *
 */
(function ($) {

  // Make sure our objects are defined.
  Drupal.CCalculator = Drupal.CCalculator || {};

  /**
   * Calculation after a click
   */
  Drupal.CCalculator.cmdCalc_Click = function(form) {
    if (form.loan_amount_2.value == 0 || form.loan_amount_2.value.length == 0) {
      alert (Drupal.t("The Principle field can't be 0!"));
      form.loan_amount_2.focus();
    }
    else if (form.compound_rate_2.value == 0 || form.compound_rate_2.value.length == 0) {
      alert (Drupal.t("The Compound Rate Percentage field can't be 0!"));
      form.compound_rate_2.focus();
    }
    else if (form.years_to_pay_2.value == 0 || form.years_to_pay_2.value.length == 0) {
      alert (Drupal.t("The Number of years field can't be 0!"));
      form.years_to_pay_2.focus();
    }
    else if (form.times_2.value == 0 || form.times_2.value.length == 0) {
      alert (Drupal.t("The Number of times Compounded can't be 0!"));
      form.times_2.focus();
    }
    else {
      results = Drupal.CCalculator.compoundCalculation(form.loan_amount_2.value, form.compound_rate_2.value, form.years_to_pay_2.value, form.times_2.value);
      form.result_2.value = results['calculation'];
    }
  }
  Drupal.CCalculator.compoundCalculation = function(loanAmount, compoundRate, yearsToPay, downPayment) {
    princ = loanAmount;
    intRate = compoundRate/100;
    months = yearsToPay;
    number_times = downPayment;
    results = new Array();
    results['calculation'] = (princ*(Math.pow((1+(intRate/number_times)),(number_times*months))));
    return results;
  }
  Drupal.behaviors.compoundCalculator = {
    attach: function (context, settings) {
      $("#compound-calculator-js-form input.button", context).click(function(event) {
        Drupal.CCalculator.cmdCalc_Click(this.form);
        return false;
      });
    }
  };
})(jQuery);