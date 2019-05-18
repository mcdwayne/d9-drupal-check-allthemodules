<?php

namespace Drupal\access_conditions_commerce_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\access_conditions_commerce\AccessConditionsCommerceCheckoutPaneTrait;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\ContactInformation as ContactInformationBase;

/**
 * Provides the contact information pane with access conditions visibility.
 *
 * @CommerceCheckoutPane(
 *   id = "access_conditions_contact_information",
 *   label = @Translation("Contact information with access conditions"),
 *   default_step = "_disabled",
 *   wrapper_element = "fieldset",
 * )
 */
class ContactInformation extends ContactInformationBase {

  use AccessConditionsCommerceCheckoutPaneTrait;

}
