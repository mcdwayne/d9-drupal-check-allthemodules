<?php

namespace Drupal\webform_craftyclicks\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;

/**
 * Provides an 'address' element with Crafty Clicks postcode lookup.
 *
 * @WebformElement(
 *   id = "webform_address_craftyclicks",
 *   label = @Translation("CraftyClicks Address"),
 *   description = @Translation("Provides a form element to collect address information (street, postcode) integrated with the Crafty Clicks postcode lookup service."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformAddressCraftyClicks extends WebformCompositeBase {


}
