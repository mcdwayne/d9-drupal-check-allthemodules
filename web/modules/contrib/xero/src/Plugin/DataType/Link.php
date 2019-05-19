<?php

namespace Drupal\xero\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Xero link
 *
 * @DataType(
 *   id = "xero_link",
 *   label = @Translation("Xero Link"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\LinkDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class Link extends Map { }
