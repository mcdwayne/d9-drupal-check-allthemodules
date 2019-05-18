<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc6;

use Drupal\commerce_migrate_ubercart\Plugin\migrate\source\Attribute as UbercartAttribute;

@trigger_error('Attribute is deprecated in Commerce Migrate 8.x-2.x-beta3 and will be removed before Commerce Migrate 8.x-3.x. Use \Drupal\commerce_migrate\modules\ubercart\source\Attribute instead.', E_USER_DEPRECATED);

/**
 * Provides migration source for attributes.
 *
 * @MigrateSource(
 *   id = "uc6_attribute",
 *   source_module = "uc_attribute"
 * )
 *
 * @deprecated in Commerce Migrate 8.x-2.x-beta3, to be removed before
 * Commerce Migrate 8.x-3.x. Use
 * \Drupal\commerce_migrate\modules\ubercart\source\Attribute instead.
 */
class Attribute extends UbercartAttribute {}
