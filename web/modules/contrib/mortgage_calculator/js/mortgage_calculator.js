/**
 * @file
 *
 * Implement a mortgage calculator.
 *
 */
(function ($) {

  // Make sure our objects are defined.
  Drupal.MCalculator = Drupal.MCalculator || {};

  /**
   * Calculation after a click
   */
  Drupal.MCalculator.cmdCalc_Click = function(form) {
    if (form.loan_amount_2.value == 0 || form.loan_amount_2.value.length == 0) {
      alert (Drupal.t("The Price field can't be 0!"));
      form.loan_amount_2.focus();
    }
    else if (form.mortgage_rate_2.value == 0 || form.mortgage_rate_2.value.length == 0) {
      alert (Drupal.t("The Interest Rate field can't be 0!"));
      form.mortgage_rate_2.focus();
    }
    else if (form.years_to_pay_2.value == 0 || form.years_to_pay_2.value.length == 0) {
      alert (Drupal.t("The years_to_pay_2 field can't be 0!"));
      form.years_to_pay_2.focus();
    }
    else {
      results = Drupal.MCalculator.mortgageCalculation(form.loan_amount_2.value, form.mortgage_rate_2.value, form.years_to_pay_2.value, 0);
      form.result_2.value = results['calculation'];
    }
  }

  /**
   * Mortgage calculation
   */
  Drupal.MCalculator.mortgageCalculation = function(loanAmount, mortgageRate, yearsToPay, downPayment) {
    princ = loanAmount - downPayment;
    intRate = (mortgageRate / 100) / 12;
    months = yearsToPay * 12;

    results = new Array();
    results['calculation'] = Math.floor((princ*intRate)/(1-Math.pow(1+intRate,(-1*months)))*100) / 100;
    results['princ'] = princ;
    results['months'] = months;

    return results;
  }

  Drupal.behaviors.mortgageCalculator = {
    attach: function (context, settings) {

      $("#mortgage-calculator-js-form input.button", context).click(function(event) {
        Drupal.MCalculator.cmdCalc_Click(this.form);
        return false;
      });

    }
  };
})(jQuery);
