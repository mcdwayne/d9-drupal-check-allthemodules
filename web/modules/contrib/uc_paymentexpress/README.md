Description
-----------

This module provide a DPS payment method for Ubercart.

Prerequisites
-------------

The Ubercart module has to be enabled.
The Payment module has to be enabled.


Installation & Configuration
----------------------------

1. Please get your DPS pxpay user ID and keys for both live & dev from www.paymentexpress.com.
2. Enable the module.
3. Add 'DPS Checkout' payment method via admin/store/config/payment
         - currently we only support single payment method instances so avoid adding additional methods once DPS has been used.
4. Add the required info on the config page (admin/store/config/payment/method/dps). If live account details are not available just use the same dev detail in the live fields.
5. DPS payment method added in 3) should now show as a payment option in the cart checkout form.
