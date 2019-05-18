<?php

namespace src\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * @DataType(
 *   id = "xero_schedule",
 *   label = @Translation("Xero Schedule"),
 *   data_definition_class = "\Drupal\xero\TypedData\Definition\ScheduleDefinition"
 * )
 */
class Schedule extends Map {}