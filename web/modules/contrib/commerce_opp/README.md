Commerce Open Payment Platform
===============
This module integrates PAY.ON [Open Payment Platform](https://docs.payon.com)
with Drupal Commerce, integrating their COPYandPAY widget in Drupal Commerce
checkout flow.

[Issue Tracker](https://www.drupal.org/project/issues/commerce_opp?version=8.x)

## Requirements

Commerce Open Payment Platform depends on Drupal Commerce of course, given a
strict dependency on commerce_payment sub module.

## Installation

It is recommended to use [Composer](https://getcomposer.org/) to get this module
with all dependencies:

```
composer require "drupal/commerce_opp"
```

See the [Drupal](https://www.drupal.org/docs/8/extending-drupal-8/installing-modules-composer-dependencies)
documentation for more details.

## Configuration

Create a new Open Payment Platform payment gateway:
  
Visit *Administration > Commerce > Configuration > Payment gateways > Add
payment gateway* and fill in the required fields. Use the API credentials
provided by your PAY.ON account. It is recommended to enter test credentials and
then override these with live credentials in settings.php. This way, live
credentials will not be stored in the DB. You also have to choose the brand(s)
(e.g. VISA) you want to support. 

There are 3 payment gateway types, you can choose from:

* Open Payment Platform COPYandPAY (bank transfer)
* Open Payment Platform COPYandPAY (credit cards)
* Open Payment Platform COPYandPAY (virtual accounts)

While you can choose multiple credit card brands for a single gateway instance,
you need to create a single instance for every bank or virtual account brand.
Why did we choose that approach? There are two main reasons for that:

1. Commerce 2.x currently does not provide additional customizing form options
   for payment gateways on the checkout page, meaning that you can't have one
   "credit card or bank transfer" entry, and then select the brand, before you
   proceed to the payment page.
2. On the payment page, COPYandPAY is only able to group credit cards together
   in one widgets. For any other brand that is defined on the payment page, a
   separate payment widget would be generated, which would cause a lot of
   confusion to your customers.

Given that reasons, we have implemented this restriction already on
configuration basis. That's also the main reason, why we are offering the three
different gateway plugins.

If someone comes up with a justified reason for offering a more generic plugin,
where the admin select any number of brands of all of these types together, I'm
open-minded to add this generic plugin as well.

### Credits
Commerce Open Payment Platform module was originally developed and is currently
maintained by [Mag. Andreas Mayr](https://www.drupal.org/u/agoradesign).

All initial development was sponsored by
[agoraDesign KG](https://www.agoradesign.at).
