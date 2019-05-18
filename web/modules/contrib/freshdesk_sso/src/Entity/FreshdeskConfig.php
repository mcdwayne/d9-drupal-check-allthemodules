<?php

/**
 * @file
 * Contains \Drupal\freshdesk_sso\Entity\FreshdeskConfig.
 */

namespace Drupal\freshdesk_sso\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\freshdesk_sso\FreshdeskConfigInterface;

/**
 * Defines the Freshdesk Configuration entity.
 *
 * @ConfigEntityType(
 *   id = "freshdesk_config",
 *   label = @Translation("Freshdesk Configuration"),
 *   handlers = {
 *     "list_builder" = "Drupal\freshdesk_sso\FreshdeskConfigListBuilder",
 *     "form" = {
 *       "add" = "Drupal\freshdesk_sso\Form\FreshdeskConfigForm",
 *       "edit" = "Drupal\freshdesk_sso\Form\FreshdeskConfigForm",
 *       "delete" = "Drupal\freshdesk_sso\Form\FreshdeskConfigDeleteForm"
 *     }
 *   },
 *   config_prefix = "freshdesk_config",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "domain" = "domain",
 *     "secret" = "secret"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/freshdesk_config/{freshdesk_config}",
 *     "edit-form" = "/admin/structure/freshdesk_config/{freshdesk_config}/edit",
 *     "delete-form" = "/admin/structure/freshdesk_config/{freshdesk_config}/delete",
 *     "collection" = "/admin/structure/visibility_group"
 *   }
 * )
 */
class FreshdeskConfig extends ConfigEntityBundleBase implements FreshdeskConfigInterface {
  /**
   * The Freshdesk Configuration ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Freshdesk Configuration label.
   *
   * @var string
   */
  protected $label;

  protected $domain;

  protected $secret;

  public function domain() {
    return $this->domain;
  }

  public function secret() {
    return $this->secret;
  }

  public function getPermissionName() {
    return 'sign into ' . $this->id();
  }

}
