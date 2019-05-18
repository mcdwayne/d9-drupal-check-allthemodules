Commerce cashpresso
===================

This module integrates the [cashpresso](https://www.cashpresso.com) payment
provider into Drupal Commerce.

cashpresso allows you to pay for purchases in installments. You can choose your installment amount when buying and
change it anytime after. 

The best thing: cashpresso always offers you a 0% option. This means you can buy right away but pay later without any
extra cost. 

To register for cashpresso is fast and works all online with a simple video call. After registering once, you have a
personal overdraft at cashpresso, with which you can pay as often as you want.

For a more comprehensive information, visit the official landing page including FAQ section (German only atm):
https://www.cashpresso.com/de/i/ratenkauf-im-onlineshop or watch this
[YouTube video](https://www.youtube.com/watch?v=6qpg7Nf-Z10).

[Issue Tracker](https://www.drupal.org/project/issues/commerce_cashpresso?version=8.x)

## Requirements

* Drupal 8
* [Commerce 2](https://drupal.org/project/commerce) (commerce_payment sub 
  module) 

## Installation

It is recommended to use [Composer](https://getcomposer.org/) to get this module
with all dependencies:

```
composer require "drupal/commerce_cashpresso"
```

See the [Drupal](https://www.drupal.org/docs/8/extending-drupal-8/installing-modules-composer-dependencies)
documentation for more details.

## Configuration

Create a new cashpresso payment gateway:
  
Visit *Administration > Commerce > Configuration > Payment gateways > Add
payment gateway* and fill in the required fields. Use the API credentials
provided by your cashpresso account. It is recommended to enter test credentials
and then override these with live credentials in settings.php. This way, live
credentials will not be stored in the DB.

**Currency restriction**

Please note, that your cashpresso account is bound to a specific currency, while Drupal Commerce allows multi-currency
solutions. The payment configuration allows you to define various kinds of conditions to limit the availability of the
payment gateway dependent on the given context. One of that conditions allows you to restrict the gateway to certain
currencies. It's therefore your responsibility to correctly configure your store. The module sends the product's
currency along with the price amount to the cashpresso API endpoint, so that misconfigured environments won't result in
wrong purchases, but will rather trigger errors by cashpresso API. 

### Product level integration

In addition to the payment gateway, this module also provides integration for
the [Product level integration](https://partner.cashpresso.com/urlreferral/api/ecommerce/v2?1#step1)
of cashpresso. This integration is attached to the 'commerce_calculated_price'
field formatter of price fields. If you're using this field to show the price on
product detail pages, the module will automatically configure upon installation
to add the product level integration. You can disable it by configuring the
entity view display configurations of your product variation bundles (e.g
/admin/commerce/config/product-variation-types/default/edit/display) and
unchecking the "Show cashpresso preview" setting of the price field.

For advanced use cases, it's quite easy to extend any field formatter of any
purchasable entity to display the product level preview. All you need to have a
look at the commerce_cashpresso_field_formatter_third_party_settings_form()
function in the commerce_cashpresso.module file and do the same for any custom
field formatter.

## Credits

Commerce cashpresso module was originally developed and is currently maintained
by [Mag. Andreas Mayr](https://www.drupal.org/u/agoradesign).

All initial development was sponsored by
* [cashpresso](https://www.cashpresso.com)
* [NOGIS](https://www.nogis.at)
* [agoraDesign KG](https://www.agoradesign.at)
