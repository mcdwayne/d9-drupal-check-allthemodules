/**
 * @file
 *
 * Implement a simple calculator.
 *
 */
(function ($) {

  // Make sure our objects are defined.
  Drupal.SCalculator = Drupal.SCalculator || {};

  /**
   * Calculation after a click
   */
  Drupal.SCalculator.cmdCalc_Click = function(form) {
    if (form.loan_amount_2.value == 0 || form.loan_amount_2.value.length == 0) {
      alert (Drupal.t("The Price field can't be 0!"));
      form.loan_amount_2.focus();
    }
    else if (form.simple_rate_2.value == 0 || form.simple_rate_2.value.length == 0) {
      alert (Drupal.t("The Interest Rate field can't be 0!"));
      form.simple_rate_2.focus();
    }
    else if (form.years_to_pay_2.value == 0 || form.years_to_pay_2.value.length == 0) {
      alert (Drupal.t("The years_to_pay_2 field can't be 0!"));
      form.years_to_pay_2.focus();
    }
    else {
      results = Drupal.SCalculator.simpleCalculation(form.loan_amount_2.value, form.simple_rate_2.value, form.years_to_pay_2.value);
      form.result_2.value = results['calculation'];
    }
  }
  Drupal.SCalculator.simpleCalculation = function(loanAmount, simpleRate, yearsToPay) {
    princ = loanAmount;
    intRate = (simpleRate);
    months = yearsToPay;
    results = new Array();
    results['calculation'] = (princ*intRate*months)/100;
    return results;
  }

  Drupal.behaviors.simpleCalculator = {
    attach: function (context, settings) {
      $("#simple-calculator-js-form input.button", context).click(function(event) {
        Drupal.SCalculator.cmdCalc_Click(this.form);
        return false;
      });
    }
  };
})(jQuery);