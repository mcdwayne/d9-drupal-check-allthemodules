<?php

namespace src\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * @DataType(
 *   id = "xero_tax_component",
 *   label = @Translation("Xero Tax Component"),
 *   data_definition_class = "\Drupal\xero\TypedData\Definition\TaxComponentDefinition"
 * )
 */
class TaxComponent extends Map {}