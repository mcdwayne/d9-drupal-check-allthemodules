<?php

namespace Drupal\xero\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\ItemList;

/**
 * @DataType(
 *   id = "xero_list",
 *   label = @Translation("Xero list"),
 *   definition_class = "\Drupal\Core\TypedData\ListDataDefinition"
 * )
 */
class XeroItemList extends ItemList { }
