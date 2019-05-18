<?php

namespace Drupal\aggrid\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the ag-Grid Entity entity.
 *
 * @ConfigEntityType(
 *   id = "aggrid",
 *   label = @Translation("ag-Grid"),
 *   handlers = {
 *     "access" = "Drupal\aggrid\AggridAccessController",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\aggrid\AggridListBuilder",
 *     "form" = {
 *       "add" = "Drupal\aggrid\Form\AggridForm",
 *       "edit" = "Drupal\aggrid\Form\AggridForm",
 *       "delete" = "Drupal\aggrid\Form\AggridDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\aggrid\AggridHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "aggrid",
 *   admin_permission = "administer aggrid config entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/aggrid/{aggrid}",
 *     "add-form" = "/admin/structure/aggrid/add",
 *     "edit-form" = "/admin/structure/aggrid/{aggrid}/edit",
 *     "delete-form" = "/admin/structure/aggrid/{aggrid}/delete",
 *     "collection" = "/admin/structure/aggrid"
 *   },
 *   config_export = {
 *     "id",
 *     "label"
 *   }
 * )
 */
class Aggrid extends ConfigEntityBase implements AggridInterface {

  /**
   * The ag-Grid ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The ag-Grid label.
   *
   * @var string
   */
  protected $label;

  /**
   * The ag-Grid Default JSON.
   *
   * @var string
   */
  protected $aggridDefault;

  /**
   * The ag-Grid additional options.
   *
   * @var string
   */
  protected $addOptions;

}
