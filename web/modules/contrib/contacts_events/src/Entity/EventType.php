<?php

namespace Drupal\contacts_events\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the Event type entity.
 *
 * @ConfigEntityType(
 *   id = "event_type",
 *   label = @Translation("Event type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\contacts_events\EventTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\contacts_events\Form\EventTypeForm",
 *       "edit" = "Drupal\contacts_events\Form\EventTypeForm",
 *       "delete" = "Drupal\contacts_events\Form\EventTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "event_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "contacts_event",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/event_type/add",
 *     "edit-form" = "/admin/structure/event_type/{event_type}",
 *     "delete-form" = "/admin/structure/event_type/{event_type}/delete",
 *     "collection" = "/admin/structure/event_type"
 *   }
 * )
 */
class EventType extends ConfigEntityBundleBase implements ConfigEntityInterface {

  /**
   * The Event type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Event type label.
   *
   * @var string
   */
  protected $label;

}
