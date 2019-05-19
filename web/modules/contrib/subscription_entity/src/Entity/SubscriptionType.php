<?php

namespace Drupal\subscription_entity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Subscription type entity.
 *
 * @ConfigEntityType(
 *   id = "subscription_type",
 *   label = @Translation("Subscription type"),
 *   handlers = {
 *     "list_builder" = "Drupal\subscription_entity\SubscriptionTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\subscription_entity\Form\SubscriptionTypeForm",
 *       "edit" = "Drupal\subscription_entity\Form\SubscriptionTypeForm",
 *       "delete" = "Drupal\subscription_entity\Form\SubscriptionTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\subscription_entity\SubscriptionTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "subscription_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "subscription",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/subscription/subscription_type/{subscription_type}",
 *     "add-form" = "/subscription/subscription_type/add",
 *     "edit-form" = "/subscription/subscription_type/{subscription_type}/edit",
 *     "delete-form" = "/subscription/subscription_type/{subscription_type}/delete",
 *     "collection" = "/subscription/subscription_type"
 *   }
 * )
 */
class SubscriptionType extends ConfigEntityBundleBase implements SubscriptionTypeInterface {

  /**
   * The Subscription type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Subscription type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Subscription type role.
   *
   * @var string
   */
  protected $role;

  /**
   * {@inheritdoc}
   */
  public function getRole() {
    return $this->role;
  }

  /**
   * {@inheritdoc}
   */
  public function setRole($role) {
    $this->role = $role;
  }

  /**
   * Gets us the site's roles.
   *
   * @return array
   *   A list of roles
   */
  public function getSiteRoles() {
    $roles = user_roles(TRUE);

    $siteRoles = [];
    foreach ($roles as $role_id => $role) {

      $siteRoles[$role_id] = $role->label();

    }
    return $siteRoles;
  }

}
