# CHANGELOG

## unreleased

* [#3053508]

## 8.x-4.2

Fix misnamed entity types in installed configuration.

## 8.x-4.1

rename hook_theme_suggestions_HOOK() to reflect new entity type names.

## 8.x-4.0

Note that upgrading from 8.x-2.x requires upgrading first to 8.x-3.0, and
only then upgrading to 8.x-4.x.

[#3052537]

## 8.x-3.0

Note, please make a database backup before this upgrade! Also, conduct it 
first on a development machine.Upgrading from 2.x to 3.x is non-trivial, 
and issues might appear that need to be resolved. The entity types provides 
by this module have had their machine names changed to be prefixed with the 
module name to align with Drupal coding standards. The renamings are:
billing_plan => braintree_cashier_billing_plan
subscription => braintree_cashier_subscription
discount => braintree_cashier_discount

To upgrade from 2.x to 3.x
* Update to the 2.0 version of Braintree Cashier or later for the datetime 
type of the Period End Date field.
* Ensure that you're using Drupal version 8.7.x or higher for the improved 
update API.
* `drush updb`
* `drush en -y migrate_drupal migrate_tools` install these temporary 
dependencies if they are not already installed.
* `drush ms` This is to confirm that the three entity type migrations are 
ready to go.
* `drush mim bc_billing_plan`
* `drush mim bc_discount`
* `drush mim bc_subscription`
* After this migration has succeeded, visit `admin/braintree-cashier/settings`
 and click the "uninstall old entity types" button. CAUTION: this will 
 delete all old entity types and their data. Ensure that the migration has 
 succeeded before doing this.
  
Be aware that when running the migrations that the insert hooks for the 
three new entity types, and the user update hooks, will be called. You 
will need to account for this in any custom hook implementations.

The theme hooks for entity types provided by this module have been renamed 
since the 2.x branch. If you have implemented hook_preprocess_ENTITY_TYPE, 
then it will need to be renamed with the new entity type name. For example, 
hook_preprocess_billing_plan would be renamed to 
hook_preprocess_braintree_cashier_billing_plan.

Any hook implementation containing ENTITY_TYPE, where ENTITY_TYPE is 
`billing_plan`, `subscription`, or `discount`, will need to be renamed 
with the prefix `braintree_cashier_`.

Entity type form ID's have also changed to reflect new entity type machine
names. hook_FORM_ID_alter() implementations will need to be updated
accordingly.

If you have added any custom fields to the old entity types, you will
need to implement `hook_migration_plugins_alter()` to migrate their
data.

* [#3041647]
* [#3014489]
* [#3041217]
* [#3041619]

## 8.x-2.1

* Prevent a user from updating their currently active subscription 
  to the same billing plan, unless the subscription is on a grace 
  period (ie. will cancel at period end).

* prevent duplicate submission of the Subscription Update form.

## 8.x-2.0

* Initial stable release.

## 8.x-2.0-rc3

WARNING: backup your database before updating since this update
involves transferring data to a new field type for the
Period End Date field.

* The `datetime` module will automatically be enabled if it isn't
  already.
* All pending entity definition updates will be automatically
  applied during the course of updating the base field type
  for the period_end_date field.

### update tasks
* Clear the cache to pick up the new QueueWorker plugin and route
  paths.
* Update the URL's for the Discount List, Subscription List, and 
  Billing Plan List Views to reflect the new collection URL's for
  these entity types. The URL's have changed to a plural suffix,
  replacing "-list" with "s", as in 
  "admin/braintree-cashier/billing-plan-list" to 
  "admin/braintree-cashier/billing-plans". See the patch in
  [#3021086] for more details.

### changes

* [#3021594]
* [#3021334]
* move processing Braintree webhooks into a Queue to avoid a race of 
  processing the same subscription simultaneously. This means webhooks
  will be processed during cron runs, not at the time the webhooks
  are received.
* [#3021086]

## 8.x-2.0-rc2

### Update tasks

Run `drush entity-updates` to pick up new date fields, and the discount
entity reference field.

Run `drush updb` to enqueue populating the new date fields with data.

The following new configuration has been added for Message templates:

field.field.message.duplicate_payment_method.field_duplicate_user.yml
field.field.message.free_trial_ended.field_subscription.yml
field.field.message.free_trial_started.field_subscription.yml
field.field.message.subscription_canceled_by_webhook.field_subscription.yml
field.field.message.subscription_ended.field_subscription.yml
field.field.message.subscription_expired_by_webhook.field_subscription.yml
field.storage.message.field_duplicate_user.yml
message.template.duplicate_payment_method.yml
message.template.free_trial_ended.yml
message.template.free_trial_started.yml
message.template.subscription_canceled_by_webhook.yml
message.template.subscription_ended.yml
message.template.subscription_expired_by_webhook.yml

Import each new configuration using the Configuration Update Manager
module: https://www.drupal.org/project/config_update
Import configuration in order according to prefix:
1) message.template.*
2) field.storage.*
3) field.field.*


Due to changing routes from /braintree-cashier to /admin/braintree-cashier,
you will need to modify the path for any View that begins with 
/braintree-cashier and change it to begin with /admin/braintree-cashier

### Other changes
* make additional fields visible when viewing a Subscription.
* Add date fields to record date free trial started, date free trial ended,
  date subscription canceled by user, and date subscription ended.
* Create update hook to populate new date fields on existing subscriptions
  with QueueWorker on cron.
* [#3016219]
* remove field_permissions dependency from duplicate_user field in Message
  template.
* fix an issue where the drop-in would be undefined if the "Confirm coupon" 
  button was pressed.
* record which discount was applied to which subscription with an 
  entity_reference field on the Subscription entity.
* replace deprecated drupal_set_message().

## 8.x-2.0-rc1

Do not use this release since it throws exceptions due to an error 
while refactoring drupal_set_message().

## 8.x-2.0-beta14

* There is a new setting to enable the coupon field on the signup form.
  Run `drush updb` to import this setting.

* [#3018032]
* [#3015823]
