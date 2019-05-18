Commerce RBS Payment
===============

Description
-----------

Integrates Commerce Payment with Russian RBS Payment Bank API.

  - Provides 'RBS Payment' payment method, which redirects user to bank's
    payment page and creates corresponding transactions in commerce orders
  - Checks transactions' states via cron and changes their state respectively
    (by default once each 5 minutes)
  - Provides link for the client to add payment to order in case the order total
    increased during confirmation call (will appear on order's "Payment" tab
    when you try to add RBS Payment payment transaction via payment terminal).
  - Provides interface for partial refund the order in case the order total
    decreased during confirmation call (available on "Payment" tab
    when you try to partially refund order via payment terminal).
  - Provides admin area link to view the list of all transactions made with
    the bank on certain day
    (admin/commerce/config/payment-methods/rbspayment/operations - today's operations,
     admin/commerce/config/payment-methods/rbspayment/operations/YYYY-mm-dd -
    for any specific date).
    If you have "Date Popup" module installed, you'll be able to switch date via UI.

There's a special mechanism to deal with orders whose customer had not followed
the link back from bank's payment page. This order remains in the system most 
likely in "checkout_payment" status.
If the transaction corresponding to this order is approved (or declined) 
by the bank and checked by the cron, order's status changes to one specified in
hook_commerce_rbspayment_unfinished_payments_statuses_info().
By default 'checkout_payment' is changed to 'checkout_complete' on approval and
to 'cart' on payment failure.

Dependencies
------------

Drupal Commerce
Entity
Elysia Cron
Views
Variable
Token


Configuration
-------------

- Commerce RBS Payment permissions

  Home > Administration > People > Permissions
  (admin/people/permissions#module-commerce_rbspayment)

- Commerce RBS Payment configuration

  Home > Administration > Store > Configuration > Payment Methods > Commerce RBS Payment settings
  (admin/commerce/config/payment-methods/rbspayment)

  You need to specify "Merchant Login" and "Password" that you get from RBS when applying
  to work with their acquiring API.

  Home > Administration > Store > Configuration > Payment Methods > Commerce RBS Payment Order tokens
  (admin/commerce/config/payment-methods/rbspayment/tokens)
  Here you need to adjust mapping of Customer profile values to be provided
  to bank when order is registered in the system.
