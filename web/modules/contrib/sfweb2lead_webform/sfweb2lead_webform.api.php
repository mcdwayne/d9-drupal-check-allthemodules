<?php

/**
 * @file
 * Hooks related to Webform module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the data to be posted to Salesforce.com.
 *
 * @param array $data
 *   The array of data to be posted, keyed on the machine-readable element name.
 *
 * @deprecated hook_posted_data_alter will be removed in 8.x-5.0 stable release.
 *   Use an event subscriber for
 *   Drupal\sfweb2lead_webform\Sfweb2leadWebformEvent instead.
 */
function hook_sfweb2lead_webform_posted_data_alter(array &$data, Drupal\webform\Entity\Webform $webform, Drupal\webform\WebformSubmissionInterface $webform_submission) {
}
