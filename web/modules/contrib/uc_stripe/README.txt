This is an Ubercart payment gateway module for Stripe.

Installation and Setup
======================

a) Use composer to install Stripe per the composer.json provided.

b) Install and enable the module in the normal way for Drupal.

b) Visit your Ubercart Store Administration page, Configuration
section, and add the Stripe gate at the Payment Methods page.
(admin/store/config/payment)

c) Configure the gateway with your Stripe API keys from https://dashboard.stripe.com/account/apikeys

d) Every site dealing with credit cards in any way should be using https. It's
your responsibility to make this happen. (Actually, almost every site should
be https everywhere at this time in the web's history.)

e) If you want Stripe to attempt to validate zip/postal codes, you must enable
that feature on your *Stripe* account settings. Click the checkbox for
"Decline Charges that fail zip code verification" on the "Account info" page.
(You must be collecting billing address information for this to work, of course.)

Limitations
===========

At this writing, the uc_recurring module is not yet available, so recurring
payments are not available.
