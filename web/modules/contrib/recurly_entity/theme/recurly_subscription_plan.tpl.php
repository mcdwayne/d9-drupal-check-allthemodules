<?php
/**
 * Default template for Recurly Subscription Plan entities.
 *
 * This template contains a $content render array with the following keys,
 * corresponding to Recurly's subscription plan objects as documented in their
 * API. See RecurlySubscriptionPlanMetadataController#entityPropertyInfo() for
 * the full definition of these properties.
 *
 *  - accounting_code
 *  - bypass_hosted_confirmation
 *  - description
 *  - display_donation_amounts
 *  - display_phone_number
 *  - display_quantity
 *  - name
 *  - plan_code
 *  - plan_interval_length
 *  - trial_interval_length
 *  - trial_interval_unit
 *  - unit_name
 */
?>
<?php print render($content); ?>
