<?php

namespace Drupal\rate_limits\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Rate Limit Config entity.
 *
 * @ConfigEntityType(
 *   id = "rate_limit_config",
 *   label = @Translation("Rate Limit Config"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\rate_limits\RateLimitConfigListBuilder",
 *     "form" = {
 *       "add" = "Drupal\rate_limits\Form\RateLimitConfigForm",
 *       "edit" = "Drupal\rate_limits\Form\RateLimitConfigForm",
 *       "delete" = "Drupal\rate_limits\Form\RateLimitConfigDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\rate_limits\RateLimitConfigHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "rate_limit_config",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/rate_limit_config/{rate_limit_config}",
 *     "add-form" = "/admin/structure/rate_limit_config/add",
 *     "edit-form" = "/admin/structure/rate_limit_config/{rate_limit_config}/edit",
 *     "delete-form" = "/admin/structure/rate_limit_config/{rate_limit_config}/delete",
 *     "collection" = "/admin/structure/rate_limit_config"
 *   }
 * )
 */
class RateLimitConfig extends ConfigEntityBase implements RateLimitConfigInterface {

  /**
   * The Rate Limit Config ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Rate Limit Config label.
   *
   * @var string
   */
  protected $label;

}
