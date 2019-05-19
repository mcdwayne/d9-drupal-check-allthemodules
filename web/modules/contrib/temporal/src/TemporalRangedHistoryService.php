<?php

/**
 * @file
 * Contains \Drupal\temporal\TemporalRangedHistoryService.
 */

namespace Drupal\temporal;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\temporal\TemporalRangeService;

/**
 * Class TemporalRangedHistoryService.
 *
 * @package Drupal\temporal
 */
class TemporalRangedHistoryService implements TemporalRangedHistoryServiceInterface {

  /**
   * Drupal\temporal\TemporalListService definition.
   *
   * @var Drupal\temporal\TemporalListService
   */
  protected $temporal_list;

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entity_type_manager;

  /**
   * Constructor.
   */
  public function __construct(TemporalListService $temporal_list, EntityTypeManagerInterface $entity_type_manager) {
    $this->temporal_list = $temporal_list;
    $this->entity_type_manager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getRangedHistory($temporal_types, $start_date, $end_date, \DateInterval $interval, \DateTimeZone $timezone = NULL) {
    return new TemporalRangedHistory($this->temporal_list, $this->entity_type_manager, $temporal_types, $start_date, $end_date, $interval, $timezone);
  }

}
