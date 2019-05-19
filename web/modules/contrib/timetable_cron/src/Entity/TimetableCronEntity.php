<?php

namespace Drupal\timetable_cron\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\timetable_cron\TimetableCronInterface;

/**
 * Defines the TimetableCron entity.
 *
 * @ConfigEntityType(
 *   id = "timetable_cron",
 *   label = @Translation("TimetableCron"),
 *   handlers = {
 *     "list_builder" = "Drupal\timetable_cron\TimetableCronListBuilder",
 *     "form" = {
 *       "add" = "Drupal\timetable_cron\Form\TimetableCronForm",
 *       "edit" = "Drupal\timetable_cron\Form\TimetableCronForm",
 *       "delete" = "Drupal\timetable_cron\Form\TimetableCronDeleteForm",
 *       "force" = "Drupal\timetable_cron\Form\TimetableCronForceForm",
 *     }
 *   },
 *   config_prefix = "timetable_cron",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "function" = "function",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/timetable_cron/{timetable_cron}",
 *     "delete-form" = "/admin/config/system/timetable_cron/{timetable_cron}/delete",
 *   }
 * )
 */
class TimetableCronEntity extends ConfigEntityBase implements TimetableCronInterface {

  /**
   * The ID of the configuration entity.
   *
   * @var int
   */
  public $id;

  /**
   * The status of the configuration entity.
   *
   * @var bool
   */
  public $status;

  /**
   * Force the cronjob on next run.
   *
   * @var bool
   */
  public $force;

  /**
   * The minute of cron run.
   *
   * @var string
   */
  public $minute;

  /**
   * The hour of cron run.
   *
   * @var string
   */
  public $hour;

  /**
   * The day of cron run.
   *
   * @var string
   */
  public $day;

  /**
   * The month of cron run.
   *
   * @var string
   */
  public $month;

  /**
   * The weekday of cron run.
   *
   * @var string
   */
  public $weekday;

  /**
   * Description of cron configuration entity.
   *
   * @var string
   */
  public $desc;

}
