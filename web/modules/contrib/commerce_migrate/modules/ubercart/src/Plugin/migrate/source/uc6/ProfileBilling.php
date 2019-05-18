<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc6;

use Drupal\commerce_migrate_ubercart\Plugin\migrate\source\ProfileBilling as UbercartProfileBilling;

@trigger_error('ProfileBilling is deprecated in Commerce Migrate 8.x-2.x-beta4 and will be removed before Commerce Migrate 8.x-3.x. Use \Drupal\commerce_migrate\modules\ubercart\source\ProfileBilling instead.', E_USER_DEPRECATED);

/**
 * Ubercart 6 billing profile source.
 *
 * @MigrateSource(
 *   id = "uc6_profile_billing",
 *   source_module = "uc_order"
 * )
 *
 * @deprecated in Commerce Migrate 8.x-2.x-beta4, to be removed before
 * Commerce Migrate 8.x-3.x. Use
 * \Drupal\commerce_migrate\modules\ubercart\source\ProfileBilling instead.
 */
class ProfileBilling extends UbercartProfileBilling {}
