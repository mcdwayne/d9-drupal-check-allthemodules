# Braintree Marketplace module

This module extends Commerce Braintree module to provide a helpful integration
starting point for marketplace transactions. As marketplace apps are by definition
more complex than traditional e-commerce, it is not intended as an out-of-the-box,
all-encompassing solution. Rather, it aims to provide developers a set of common,
required elements for interacting with Braintree's marketplace API.

## Webhook endpoint

A webhook endpoint is exposed at `/commerce_braintree_marketplace/callback` and
supports the following webhook types:

* `WebhookNotification::CHECK`
* `WebhookNotification::SUB_MERCHANT_ACCOUNT_APPROVED`
* `WebhookNotification::SUB_MERCHANT_ACCOUNT_DECLINED`
* `WebhookNotification::DISBURSEMENT_EXCEPTION`
* `WebhookNotification::DISBURSEMENT`

With the exception of the `CHECK` event, the controller merely fires an event
for other modules to perform actions consistent with your business use case.

## Submerchant architecture

This module tries to make few assumptions about your site architecture, however
it does ship a profile `seller` bundle with a locked `braintree_id` remote ID
field for matching payments to submerchants.

### Contributing

Contributions are welcome. This module was largely created out of a single site's
requirements so your edge case might not have been considered (yet).
