CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Basic usage
 * Recommended modules
 * Maintainers


INTRODUCTION
------------

The Omeda Customers module extends the Omeda base module to allow syncing of
Drupal user data with Omeda using the Store Customer and Order API. You can map
user fields to your Omeda customer entities and sync them on user updates. You
can choose which roles will sync and which user fields will sync.

Since field mapping can get quite hairy, we provide a simple solution for simple
use cases where you need to simply tell Omeda that “this field = that field”.
If it’s a standard base Omeda field like “First Name”, those are called base
fields and are meant to be a simple hand off of the Drupal field value to Omeda.
If it’s an email, phone number, or address field, you can choose that type and
we will then ask you to determine which contact type it represents so that it
gets into Omeda properly. It should be noted that for addresses, we only support
Address fields from the Address module since mapping a bunch of individual
Drupal fields to a single Address entity in Omeda is more complicated and likely
needs a custom solution.

This mapping config also provides a simple solution for Omeda Demographic
fields, which are more complex and dynamic fields that store Ids instead of
literal values. It allows you to choose which demographic field a Drupal user
field maps to and then create a mapping of possible Drupal field values with
available Omeda field values. So if you have a field on the Drupal user called
“Primary business role”, but you want to map it to the Omeda “Job Title”
demographic field, you can do that. You would then hand enter a mapping that
indicates that a Drupal field value of “President” maps to the Omeda value of
“President / CEO” so that we can send Omeda it’s desired Id value of 5*******
instead of the literal text of “President / CEO”, which would be invalid. Again,
this is for more simple use cases because in the case Omeda fields don’t map
1-to-1 to Drupal fields, the necessary business logic is wide-ranging and you
will likely need custom programming. If this is needed, we've included support
for a custom hook (hook_omeda_customer_data_alter) to inject yourself into the
data mapping process and provide your own custom alterations.

To submit bug reports and feature suggestions, or to track changes:
  https://www.drupal.org/project/issues/omeda_customers

To access the Omeda API documentation, visit
https://jira.omeda.com/wiki/en/Wiki_Home


REQUIREMENTS
------------

This module requires to Omeda and Encryption modules.


INSTALLATION
------------

Install this module via composer by running the following command:
* composer require drupal/omeda_customers


CONFIGURATION
------------

Configure Omeda Subscriptions in Administration » Configuration » Omeda »
Omeda Customers Settings, or by going directly to /admin/config/omeda/customers:

* User Sync Enabled
  This determines whether the system will attempt to sync Omeda users on create
  or update. This is in place to allow full configuration of the module before
  turning the sync on.
* Force Immediate Execution
  If this is enabled, the Omeda runProcessor API is called immediately after the
  Save Customer and Order API to force an immediate sync. Otherwise queue wait
  times are up to 15 minutes in production and 30 minutes in staging. With high
  traffic, this should probably be left off.
* External Customer Id Namespace
  When making the Store Customer and Order API call, if the External Customer Id
  Namespace setting is populated it is sent as the ExternalCustomerIdNamespace
  and the UUID of the user being saved gets sent as the ExternalCustomerId. If
  this setting is not populated, and the Drupal user email field is set to sync,
  Customer Lookup By Email is first called and if a match is found, the
  OmedaCustomerId is sent along.
* Roles to Sync
  This determines which user roles will be synced to Omeda.
* Field Mappings
  This allows you to choose which Drupal user fields to sync to Omeda and how
  they map. This covers simple base Omeda customer fields as well as Emails,
  Phone Numbers, Addresses and Demographic fields.


BASIC USAGE
------------

All that is needed is to enable the module, configure it properly, and enable
user sync. If you wish to override the default data that is passed to the Omeda
Save Customer and Order API call, you can use hook_omeda_customer_data_alter to
alter the data after the module has set it up, but before it is passed to the
API.


RECOMMENDED MODULES
------------

* Omeda (https://drupal.org/project/omeda)
* Omeda Customers (https://drupal.org/project/omeda_customers)


MAINTAINERS
------------

Current maintainers:
 * Clint Randall (camprandall) - https://drupal.org/u/camprandall
 * Jay Kerschner (JKerschner) - https://drupal.org/u/jkerschner
 * Brian Seek (brian.seek) - https://drupal.org/u/brianseek
 * Mike Goulding (mikeegoulding) - https://drupal.org/user/mikeegoulding

This project has been sponsored by:
 * Ashday Interactive
   Building your digital ecosystem can be daunting. Elaborate websites, complex
   cloud applications, mountains of data, endless virtual wires of integrations.
