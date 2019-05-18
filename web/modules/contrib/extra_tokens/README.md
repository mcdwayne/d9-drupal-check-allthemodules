Extra tokens
===============

CONTENTS OF THIS FILE
---------------------

  * Introduction
  * Requirements
  * Installation
  * Using the module
  * Author

INTRODUCTION
------------

Core module taxonomy does not provide relative url token for taxonomy term, this module adds it.
If you want node's url to display the nesting of related terms, this will needed when building breadcrumbs with
module easy_thumbnails simply install extra_tokens and configure pathauto.

If you need simply display price at different currencies at ckeditor this module creates filter convert_to_currency 
for conventional on currency to another one and Twig text formatter for rendering text as text twig template.

If you need to provide one time login link to your commerce order in email this module provide controller that will handle auth by token and variable that can be used at commerce-order-receipt.html.twig.



REQUIREMENTS
------------

Taxonomy

INSTALLATION
------------

1. Install module as usual via Drupal UI, Drush or Composer.

USING THE MODULE
----------------

1. Go to /admin/config/search/path/patterns edit pattern
2. Set pattern to [node:field_section:entity:url-relative]/[node:title]  Replace field_section to your taxonomy field.
3. Go to /admin/config/search/path/settings Punctuation set Slash (/) to No action

Using convert_to_currency filter
1. Go to module configuration /admin/config/extra-tokens/currencies provide BASE_CURRENCY and exchange rates.
2. Go to manage display at content type /admin/structure/types and select "Twig text" as field formatter
3. Now edit node and use filter as for example: {{ '89.00'|convert_to_currency('USD', 'UAH') }}


Using one time login link
1. Edit email template email/commerce-order-receipt.html.twig. Place {{ 'To view order information go to'|t }}  {{ link_to_orders }}

AUTHOR
------

shmel210  
Drupal: (https://www.drupal.org/user/2600028)  
Email: shmel210@zina.com.ua

Company: Zina Design Studio
Website: (https://www.zina.design)  
Drupal: (https://www.drupal.org/user/361734/)  
Email: info@zina.design