<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc6;

use Drupal\commerce_migrate_ubercart\Plugin\migrate\source\TaxTypeBase;

/**
 * Gets the Ubercart tax rates.
 *
 * @MigrateSource(
 *   id = "uc6_tax_type",
 *   source_module = "uc_taxes"
 * )
 */
class TaxType extends TaxTypeBase {}
