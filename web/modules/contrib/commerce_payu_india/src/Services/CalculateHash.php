<?php

 namespace Drupal\commerce_payu_india\Services;

/**
 * Class to generate hash code.
 */
class CalculateHash {

  /**
   * Generate Hash code to send as Post parameter.
   */
  public function commercePayUIndiaGetHash($params, $salt) {
    $posted = [];

    if (!empty($params)) {
      foreach ($params as $key => $value) {
        $posted[$key] = htmlentities($value, ENT_QUOTES);
      }
    }

    $hash_sequence = "key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10";

    $hash_vars_seq = explode('|', $hash_sequence);
    $hash_string = NULL;

    foreach ($hash_vars_seq as $hash_var) {
      $hash_string .= isset($posted[$hash_var]) ? $posted[$hash_var] : '';
      $hash_string .= '|';
    }

    $hash_string .= $salt;
    return strtolower(hash('sha512', $hash_string));
  }

  /**
   * Generate reverse hash code for validation after transaction is completed.
   */
  public function commercePayUIndiaReverseHash($params, $salt, $status) {
    $posted = [];
    $hash_string = NULL;

    if (!empty($params)) {
      foreach ($params as $key => $value) {
        $posted[$key] = htmlentities($value, ENT_QUOTES);
      }
    }
    $additional_hash_sequence = 'base_merchantid|base_payuid|miles|additional_charges';
    $hash_vars_seq = explode('|', $additional_hash_sequence);

    foreach ($hash_vars_seq as $hash_var) {
      $hash_string .= isset($posted[$hash_var]) ? $posted[$hash_var] . '|' : '';
    }

    $hash_sequence = "udf10|udf9|udf8|udf7|udf6|udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key";
    $hash_vars_seq = explode('|', $hash_sequence);
    $hash_string .= $salt . '|' . $status;

    foreach ($hash_vars_seq as $hash_var) {
      $hash_string .= '|';
      $hash_string .= isset($posted[$hash_var]) ? $posted[$hash_var] : '';
    }

    return strtolower(hash('sha512', $hash_string));
  }

}
