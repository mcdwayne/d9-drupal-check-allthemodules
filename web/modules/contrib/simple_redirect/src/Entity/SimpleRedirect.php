<?php

namespace Drupal\simple_redirect\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Simple Redirect entity.
 *
 * @ConfigEntityType(
 *   id = "simple_redirect",
 *   label = @Translation("Simple Redirect"),
 *   handlers = {
 *     "list_builder" = "Drupal\simple_redirect\SimpleRedirectListBuilder",
 *     "form" = {
 *       "add" = "Drupal\simple_redirect\Form\SimpleRedirectForm",
 *       "edit" = "Drupal\simple_redirect\Form\SimpleRedirectForm",
 *       "delete" = "Drupal\simple_redirect\Form\SimpleRedirectDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\simple_redirect\SimpleRedirectHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "simple_redirect",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "from" = "from",
 *     "to" = "to"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/simple_redirect/{simple_redirect}",
 *     "add-form" = "/admin/config/simple_redirect/add",
 *     "edit-form" = "/admin/config/simple_redirect/{simple_redirect}/edit",
 *     "delete-form" = "/admin/config/simple_redirect/{simple_redirect}/delete",
 *     "collection" = "/admin/config/simple_redirect"
 *   }
 * )
 */
class SimpleRedirect extends ConfigEntityBase implements SimpleRedirectInterface {

  /**
   * The Simple Redirect ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Simple Redirect label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Simple Redirect from url.
   *
   * @var string
   */
  protected $from;

  /**
   * The Simple Redirect from url.
   *
   * @var string
   */
  protected $to;

  /**
   * @inheritdoc
   */
  public function getFrom() {
    return $this->from;
  }

  /**
   * @inheritdoc
   */
  public function getTo() {
    return $this->to;
  }
}
