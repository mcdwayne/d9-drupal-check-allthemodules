<?php

namespace Drupal\blockchain\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\StringLongItem;

/**
 * Class BlockchainDataItem basically extends 'string'.
 *
 * @FieldType(
 *   id = "blockchain_data",
 *   label = @Translation("Blockchain data"),
 *   description = @Translation("A field containing blockchain block data value."),
 *   category = @Translation("Text"),
 *   default_widget = "blockchain_data_widget",
 *   default_formatter = "blockchain_data_formatter"
 * )
 *
 * @package Drupal\blockchain\Plugin\FieldType
 */
class BlockchainDataItem extends StringLongItem {}
