<?php
/**
 * @file
 * Contains Drupal\social_counters\Entity\SocialCountersConfig.
 */
namespace Drupal\social_counters\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Social Counters Config entity.
 *
 * @ConfigEntityType(
 *   id = "social_counters_config",
 *   label = @Translation("Social Counters Config"),
 *   admin_permission = "administer social counters",
 *   handlers = {
 *     "access" = "Drupal\social_counters\SocialCountersConfigAccessControlHandler",
 *     "list_builder" = "Drupal\social_counters\Entity\Controller\SocialCountersConfigListBuilder",
 *     "form" = {
 *       "add" = "Drupal\social_counters\Form\SocialCountersConfigForm",
 *       "edit" = "Drupal\social_counters\Form\SocialCountersConfigForm",
 *       "delete" = "Drupal\social_counters\Form\SocialCountersConfigDeleteForm",
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/services/social_counters/{social_counters_config}/edit",
 *     "delete-form" = "/admin/config/services/social_counters/{social_counters_config}/delete",
 *     "collection" = "/admin/config/services/social_counters/list"
 *   },
 * )
 */
class SocialCountersConfig extends ConfigEntityBase {
   /**
   * The Social Counters Config ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Social Counters Config UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The Social Counters label.
   *
   * @var string
   */
  public $name;

  /**
   * The Plugin id.
   *
   * @var string
   */
  public $plugin_id;

   /**
   * Configuration.
   *
   * @var string
   */
  public $config;
}
