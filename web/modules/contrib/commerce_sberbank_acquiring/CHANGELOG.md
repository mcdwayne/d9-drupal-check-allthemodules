Changelog
==========

Current dev
------------

 * Improved `$order_id` generation. Payment is force saved before connect to Sberbank, so there is no chance for `$order_id` to be empty now.
 * Payments are now using states:

   - `new`: Payment created for Sberbank registration.
   - `authorization`: Order was registered in Sberbank successfully, and user must be redirect to payment form. Also, after successful registration, payment already store remote order id (sberbank order id).
   - `authorization_voided`: If order registration is failed.
   - `completed`: If payment was successful and user was redirected to complete url callback with Sberbank order status 'DEPOSITED'.

 * Payment is now store remote order id since successful order registration (before user redirect). So, if something goes wrong, it can help to further investigations.

