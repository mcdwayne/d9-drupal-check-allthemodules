# Installation
This module is installed via the typical processes for Drupal 8.

# Configuration

## User field
This payment gateway gives you the option of requiring users to be
approved to use purchase orders. When it is installed it adds a field,
_Purchase Orders Authorized_ to the User entity.  By default this field
is not displayed.  To use this field, and require user pre-approval for
Purchase Orders, begin by browsing
to `admin/config/people/accounts/form-display` and move this field
from **Disabled** into the form.

## Payment Gateways
Payment gateways are added at `admin/commerce/config/payment-gateways`.
Commerce Purchase Order adds the following to the gateway configuration
form:

- _Limit maximum open purchase orders_ The number of unpaid purchase orders a customer has can be used to prevent new purchases.  See _Workflow_ below.
- _Purchase order users require approval in the user account settings_ When selected, the value of _Purchase Orders Authorized_ is examined when the customer "pays and completes" the order.  If the field is not checked (is FALSE) then the payment is denied (not authorized).
- _Payment instructions_ Formatted text instructing the user how to pay their Purchase Order amount.  Displayed at checkout and in the confirming email.
- _Conditions: Customer - Limit by field: Purchase Orders Authorized_ This condition is useful when Commerce Purchase Order is one of multiple gateways enabled.  When selected the gateway is only offered as an option if the Customer is approved to use it.  See the discussion of the field _Purchase Orders Authorized_ above.

# Workflow
Commerce Purchase Orders normally progress through the following:

1. _New_ The checkout process has begun and a PO number assigned to a one-time payment method.
2. _Authorized_ The customer has submitted the request to pay, and the authorization options defined in the payment gateway have been evaluated and passed.
3. _Completed_ The payment is saved and the user has checked out.  The Purchase Order has not been paid.
4. _Paid_  A user with permission to administer payments has browsed to the payment tab of the relevant order, selected **Receive** as the operation, and recorded the payment by saving the _Receive Payment_ form.
