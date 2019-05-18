/**
 * Display calc
 */
jQuery(document).ready(function() {
  jQuery("#calc-wrapper").show();
});

/**
 * To store last calculated value.
 */
var x = 0;

/**
 * To check the operation req. whether +,-,*,/ or =.
 */
var ops = "n";

/**
 * To check if result values needs to be reset or not.
 */
var token = 0;

/**
 * Function to return result for the operation performed on calc.
 */
function calc(op) {
  result = jQuery("#edit-calc-result").val();
  if (op == "1" || op == "2" || op == "3" || op == "4" || op == "5" || op == "6" || op == "7" || op == "8" || op == "9" || op == "0" || op == ".") {
    if (!token) {
      if (result == 0) {
        jQuery("#edit-calc-result").val(op);
      }
      else {
        jQuery("#edit-calc-result").val(result + op);
      }
    }
    else {
      jQuery("#edit-calc-result").val(op);
      token = 0;
    }
    return;
  }

  if (op == "C") {
    jQuery("#edit-calc-result").val(0);

    x = 0;
    token = 0;
    return;
  }

  if (op == "%") {
    jQuery("#edit-calc-result").val(result / 100.0);

    token = 1;
    return;
  }

  if (op == "+/-") {
    jQuery("#edit-calc-result").val(-result);

    token = 1;
    return;
  }

  if (op == "1/x") {
    if (result != 0) {
      jQuery("#edit-calc-result").val(1 / result);
    }
    else {
      jQuery("#edit-calc-result").val(1 / result);
    }

    token = 1;
    return;
  }


  if (op == "+" || op == "-" || op == "*" || op == "/" || op == "=") {
    token = 1;

    if (ops != "n") {
      if (ops == "+") {
        x = x -(- result);
        jQuery("#edit-calc-result").val(x);
      }

      if (ops == "-") {
        toFixedVal = 0;

        if (result.indexOf(".") != -1) {
          toFixedVal = (x.length > result.length) ? ((x.split(".")[1] > 0) ? x.split(".")[1].length : 0) : ((result.split(".")[1] > 0) ? result.split(".")[1].length : 0);
          x = x - result;
          x = x.toFixed(toFixedVal);
        }
        else {
          x = x - result;
        }

        jQuery("#edit-calc-result").val(x);
      }

      if (ops == "/") {
        if (x > 0) {
          x = x / result;
          jQuery("#edit-calc-result").val(x);
        }
      }

      if (ops == "*") {
        if (x > 0) {
          x = x * result;
          jQuery("#edit-calc-result").val(x);
        }
      }
    }
    else {
      x = result;
    }

    if (op != "=") {
      ops = op;
    }
    else {
      ops = "n";
    }

    return;
  }
}
