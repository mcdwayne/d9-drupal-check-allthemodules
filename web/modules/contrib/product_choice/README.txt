CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ

INTRODUCTION
------------

Product Choice is a product management module that works with Commerce Product
Types. It helps you better control the uniformity and standardization of your
product specification data.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/product_choice

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/product_choice

REQUIREMENTS
------------

This module requires the following modules:

 * Commerce (https://drupal.org/project/commerce)
   - Commerce Product, Commerce Price, and Commerce Store
 * Address (https://www.drupal.org/project/address)
 * Entity (https://www.drupal.org/project/entity)
 * Inline Entity Form (https://www.drupal.org/project/inline_entity_form)
 * Core
   - Field, Filter, Path, System, Text, Toolbar, User, Views
 * Core Field Types
   - Datetime, Image, Text

INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.

CONFIGURATION
-------------
 
 * Configure user permissions in Administration » People » Permissions:

   - Use administration pages and help (System)

     Users need access to the administration pages in order to configure this
     module.

   - Use the administration toolbar (System)

     Users need access to the administration pages in order to configure this
     module.

   - Use the commerce administration pages (Commerce module)

     Users with the "Use the commerce administration pages" permission will see
     a "Product choice lists" menu item on the Commerce Configuration page.

   - Edit product choice terms (Product Choice module)

     This permission enables a user to edit the terms that appear on any product
     choice lists.

   - Administer product choice lists (Product Choice module)

     This permission grants the user full administrative control over product
     choice lists and terms.

   - Administer product types (Commerce Product)

     This permission allows a user to create an entity reference field for a
     product type that uses a product choice term.

 * Administer the product choice lists and terms in Administration » Commerce »
   Configuration » Product choice lists.

TROUBLESHOOTING
---------------

 * The module currently has no known issues.

FAQ
---

 * No FAQs yet exist.
