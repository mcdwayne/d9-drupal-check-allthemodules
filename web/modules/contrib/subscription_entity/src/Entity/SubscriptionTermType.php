<?php

namespace Drupal\subscription_entity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Subscription Term type entity.
 *
 * @ConfigEntityType(
 *   id = "subscription_term_type",
 *   label = @Translation("Subscription Term type"),
 *   handlers = {
 *     "list_builder" = "Drupal\subscription_entity\SubscriptionTermTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\subscription_entity\Form\SubscriptionTermTypeForm",
 *       "edit" = "Drupal\subscription_entity\Form\SubscriptionTermTypeForm",
 *       "delete" = "Drupal\subscription_entity\Form\SubscriptionTermTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\subscription_entity\SubscriptionTermTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "subscription_term_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "subscription_term",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/subscription_term_type/{subscription_term_type}",
 *     "add-form" = "/admin/structure/subscription_term_type/add",
 *     "edit-form" = "/admin/structure/subscription_term_type/{subscription_term_type}/edit",
 *     "delete-form" = "/admin/structure/subscription_term_type/{subscription_term_type}/delete",
 *     "collection" = "/admin/structure/subscription_term_type"
 *   }
 * )
 */
class SubscriptionTermType extends ConfigEntityBundleBase implements SubscriptionTermTypeInterface {

  /**
   * The Subscription Term type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Subscription Term type label.
   *
   * @var string
   */
  protected $label;

}
