<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc6;

use Drupal\commerce_migrate_ubercart\Plugin\migrate\source\AttributeValue as UbercartAttributeValue;

@trigger_error('AttributeValue is deprecated in Commerce Migrate 8.x-2.x-beta3 and will be removed before Commerce Migrate 8.x-3.x. Use \Drupal\commerce_migrate\modules\ubercart\source\AttributeValue instead.', E_USER_DEPRECATED);

/**
 * Provides migration source for AttributeValues.
 *
 * @MigrateSource(
 *   id = "uc6_Attribute_value",
 *   source_module = "uc_Attribute_value"
 * )
 *
 * @deprecated in Commerce Migrate 8.x-2.x-beta3, to be removed before
 * Commerce Migrate 8.x-3.x. Use
 * \Drupal\commerce_migrate\modules\ubercart\source\AttributeValue instead.
 */
class AttributeValue extends UbercartAttributeValue {}
