INTRODUCTION
------------

The Commerce GoCardless module provides support for commerce payments using
GoCardless one off payments.


REQUIREMENTS
------------

This module requires the following modules:

 * Commerce (https://drupal.org/project/commerce)

In addition, the following patches are required:

 * https://www.drupal.org/project/commerce/issues/2930500: Add pending states
   to the payment_default workflow

The following patches are recommended:

 * https://www.drupal.org/project/commerce/issues/2930512: Do not allow payment
   gateways that are offsite/use SupportsNotificationsInterface with an order
   that doesn't have a validation state.


INSTALLATION
------------

The following steps illustrate setting up a GoCardless sandbox account and this
Drupal module to work together:

_GoCardless:_

1. Create a sandbox account at https://developer.gocardless.com.

2. Create an access token with read+write access. Remember this for now.

_Drupal:_

3. Install the `commerce_gocardless` module.

4. Ensure the _GoCardless mandate_ pane is enabled on the _review_ step of
   the checkout flow. See the implementation details section below for details
   of what this does and why it is necessary.

5. Create a payment gateway using the GoCardless plugin.
   The mode should be `sandbox`.

6. In the _description_ field, put something identifying your store.
   This will be shown to users when they visit gocardless.com to supply their
   bank account details.

6. In the _access token_ field, put the access token obtained in step 2 above.

7. In the _webhook secret_ field, put a suitable password. This will be shared
   with GoCardless. It is used to authenticate requests originating from
   GoCardless.

8. Make a note of the payment gateway's machine name, we will need this later.

9. Change the default order type to use a workflow that uses validation, or set
   up a new order type for this and ensure that the GoCaardless payment gateway
   can only use this order type.

_GoCardless:_

9. Create a webhook endpoint.

10. The URL for the webhook is based on the path to your Drupal site and
    includes the machine name of the payment gateway entity, as noted in
    step 8 above. For example:

        https://www.example.com/commerce-gocardless/webhook/{payment_gateway}

11. Use the secret defined in step 7 above here.


IMPLEMENTATION DETAILS
----------------------

### Payment method type

There is a custom payment method type - _GoCardless one-off payment_
(`commerce_gocardless_oneoff`). Payments of this type hold the corresponding
GoCardless mandate ID in the _remote ID_ field.

### Payment gateway

There is a custom payment gateway plugin - `GoCardlessPaymentGateway`.
This can use existing direct debit mandates or create new ones using the
GoCardless API.

### Checkout setup

The checkout process is slightly different for direct debits as for other
kinds of payment. Direct debit involves setting up a _mandate_ before any
payments can be made. Users must supply their bank account details to
GoCardless, therefore they are redirected their as part of the checkout
process. However, this is an _on-site_ gateway - payment is not taken during
mandate creation, and once the mandate exists the gateway can initiate a
payment without the user being redirected off-site.

To facilitate mandate creation, there is a new checkout pane plugin:
`GoCardlessMandatePane`. This should be added to the _review_ step of the
checkout process.

This pane examines the payment method for the order.
If it is a GoCardless one-off payment but without a mandate ID (i.e. the
user selected _new direct debit_ as the payment method), the pane redirects
the user to GoCardless for them to supply their bank details. When the user
is later returned to the checkout, the pane does nothing and checkout can
continue as normal.

For payment methods that already have a mandate ID set (i.e. the user
selected _existing direct debit_ as the payment method), or if the payment
method is something other than GoCardless, this pane does nothing.

Therefore it can can safely be used in a checkout workflow even if GoCardless
is not used, and you can mix GoCardless with other kinds of payment if you
wish.

## Checkout process

Add a product to the cart, view the cart and click _checkout_ to start the
process.

You have the option of choosing a payment type, which may include existing
GoCardless direct debit mandates. You also have an option of choosing a new
direct debit. Note that choosing new direct debit here results in a new
payment method being created, with no mandate ID value set. That is
populated later upon returning from GoCardless.

Immediately prior to the review step you may be directed to GoCardless to set
up the mandate. The address and user's email address are pre-populated.

The following bank details can be used for testing:
- sort code:  200000
- account no: 44779911

GoCardless then redirects you back to Drupal, to the
`MandateConfirmationController`.

### Mandate confirmation

`MandateConfirmationController` does the following:
- confirms with GoCardless, whereby a customer/mandate is created.
- updates the stored payment method (of type `commerce_gocardless_oneoff`,
  with the appropriate mandate ID stored in the payment method's remote ID)
- redirects you to the checkout workflow

### Payment confirmation

The `GoCardlessPaymentGateway` is called to create the payment itself.
It calls the GC API and receives back a remote payment ID. This is stored
within the Drupal Commerce payment entity's remote ID property.



## Webhooks

GoCardless provide notifications of payments (amongst other things).

The URL for the webhook is specific to the payment gateway instance.
You will need to know the machine name of the payment gateway:

`http://example.com/commerce-gocardless/webhook/{payment_gateway}`


### Payment notifications for an existing order

In this case we simply want to mark a payment as complete or rejected. The
order should then be resolved as appropriate. This is the same as with offsite
payment mechanisms where the payment is not processed immediately.
