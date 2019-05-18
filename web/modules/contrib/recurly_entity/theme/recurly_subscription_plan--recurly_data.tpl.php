<?php
/**
 * Recurly data template for Recurly Subscription Plan entities.
 *
 * This template contains a $content render array with the following keys,
 * corresponding to Recurly's subscription plan objects as documented in their
 * API. See RecurlySubscriptionPlanMetadataController#entityPropertyInfo() for
 * the full definition of these properties.
 *
 * Unlike the 'full' view mode, the recurly_data view mode will only show
 * Recurly properties and not any fields added to the subscription plan entity.
 */
?>
<?php print render($content); ?>
