<?php

namespace Drupal\xero\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Xero detail item type
 *
 * @DataType(
 *   id = "xero_detail",
 *   label = @Translation("Xero Detail"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\DetailDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class Detail extends Map {}
