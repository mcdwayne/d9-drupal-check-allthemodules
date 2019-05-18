<?php

/**
 * @file
 * Hooks and documentation related to druminte_webforms module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the Druminate Donation Handler fields.
 *
 * @param array $donationFields
 *   Array of additional fields to be passed to the Druminate Donation Handler.
 *   More information and additional fields can be found at
 *   http://open.convio.com/api/#donation_api.donate_method.html.
 */
function as_druminate_druminate_webforms_donation_fields_alter(array &$donationFields) {
  $donationFields[] = (object) ['elementName' => 'source'];
  $donationFields[] = (object) ['elementName' => 'sub_source'];
}

/**
 * @} End of "addtogroup hooks".
 */
