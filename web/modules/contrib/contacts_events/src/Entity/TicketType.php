<?php

namespace Drupal\contacts_events\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Ticket type entity.
 *
 * @ConfigEntityType(
 *   id = "contacts_ticket_type",
 *   label = @Translation("Ticket type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\contacts_events\TicketTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\contacts_events\Form\TicketTypeForm",
 *       "edit" = "Drupal\contacts_events\Form\TicketTypeForm",
 *       "delete" = "Drupal\contacts_events\Form\TicketTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "\Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "contacts_ticket_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "contacts_ticket",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/contacts_ticket_type/{contacts_ticket_type}",
 *     "add-form" = "/admin/structure/contacts_ticket_type/add",
 *     "edit-form" = "/admin/structure/contacts_ticket_type/{contacts_ticket_type}/edit",
 *     "delete-form" = "/admin/structure/contacts_ticket_type/{contacts_ticket_type}/delete",
 *     "collection" = "/admin/structure/contacts_ticket_type"
 *   }
 * )
 */
class TicketType extends ConfigEntityBundleBase implements TicketTypeInterface {

  /**
   * The Ticket type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Ticket type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The stores through which the tickets are sold.
   *
   * @var string
   */
  protected $stores = [];

}
