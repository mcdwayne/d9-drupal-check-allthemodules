# paypal_subscriptions
PayPal Subscriptions for Commerce 2.x

An extension of the commerce paypal module which allows you to use recurring payments through the paypal express checkout method.

This module contains:
- A payment gateway for PayPal subscriptions.

Note that this uses the express checkout method through paypal.

Features
--------
- Be able to make a payment through PayPal Express checkout.
- Be able to select frequency of Billing.
- Be able to create a billing agreement for users.

Requirements
------------
- commerce_paypal

Payment Gateway
---------------

This payment gateway forces the user to go through subsciptions in paypal. A billing agreement is create and then a user is billed at the desired interval.
