<?php

namespace Drupal\commerce_adyen\Adyen\Controller;

use Drupal\commerce_adyen\Adyen\Facade;

/**
 * Base checkout controller.
 */
abstract class Checkout extends Controller {

  use Facade;

  /**
   * Build checkout form to allow customers fill additional data.
   *
   * @return array[]
   *   Form items.
   */
  abstract public function checkoutForm();

  /**
   * Validate and process user input of checkout form.
   *
   * @param array[] $form
   *   Form items.
   * @param array $values
   *   Submitted values.
   *
   * @return bool
   *   A state of validation. In case of FALSE, customer will
   *   not be redirected to payment gateway. Here you're able
   *   to use "form_error()" function to indicate what's going on.
   */
  public function checkoutFormValidate(array $form, array &$values) {
    return TRUE;
  }

}
