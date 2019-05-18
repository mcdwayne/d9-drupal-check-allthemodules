# Mailchimp Popup Block module for Drupal 8.x.
----------------------------------------------------------------

CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Theming
* Maintainers


INTRODUCTION
------------
  
The Mailchimp Popup Block module provides a block with a button, that triggers
the opening of the `Subscriber pop-up`.

The [Mailchimp module](https://www.drupal.org/project/mailchimp) and the
Mailchimp API currently does not provide a GDPR compliant implementation.
Therefore it cannot be used for websites with an EU audience anymore.

According to the Mailchimp documentation, the `Subscriber pop-up` form is the
only GDPR compliant embedding option. This module provides an integration for
this pop-up, which is triggered by clicking on a specific element.


REQUIREMENTS
------------

This module requires the following modules:

* Block ([Drupal core](http://drupal.org/project/commerce))


INSTALLATION
------------

Install the module as usual, more info can be found on:
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules


CONFIGURATION
-------------

* Go to the Block layout configuration (`/admin/structure/block`)
  * Alternative: Use Panels or Layout Builder
* Add a Mailchimp Popup Block
* Specify the Mailchimp configuration
  * Mailchimp Base URL: Base URL of your Mailchimp instance.
  * Mailchimp UUID: The UUID of your Mailchimp account.
  * Mailchimp List Id: The Mailchimp List Id the user should subscribe to.
* The configuration variables can be fetched in the [Mailchimp Subscriber pop-up
configuration](https://mailchimp.com/help/add-a-pop-up-signup-form-to-your-website/#Add_Form_to_Multiple_Sites)
* The popup design itself can be configured on the [Mailchimp Subscriber pop-up
configuration](https://mailchimp.com/help/add-a-pop-up-signup-form-to-your-website/).


Theming
-------

The module does not provide any styling, because every site is unique. You can
style the provided markup of `templates/templates/mailchimp-popup.html.twig` or
override the Twig file.

You can also make us of the Popup functionality, without using the configurable
block at all. You simply need to attach the library and add a HTML element with
the required data properties.

```html
{{ attach_library('mailchimp_popup_block/mailchimp_popup_block') }}
<a href="#" data-mailchimp-popup-block-baseurl="{{ mailchimp_baseurl }}"
data-mailchimp-popup-block-uuid="{{ mailchimp_uuid }}"
data-mailchimp-popup-block-lid="{{ mailchimp_lid }}">Click me</a>
```


MAINTAINERS
-----------

Current maintainers:
- Stephan Zeidler (szeidler) - https://www.drupal.org/user/767652
