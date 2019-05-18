<?php

/**
 * @file
 * Contains Drupal\maestro\Entity\Template.
 *
 * This contains our entity class.
 *
 * credits: originally based on code from blog post at
 * http://previousnext.com.au/blog/understanding-drupal-8s-config-entities
 */

namespace Drupal\maestro\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the MaestroTemplate entity.
 *
 * The lines below are a plugin annotation. These define the entity type to the
 * entity type manager.
 *
 * The properties in the annotation are as follows:
 *  - id: The machine name of the entity type.
 *  - label: The human-readable label of the entity type. We pass this through
 *    the "@Translation" wrapper so that the multilingual system may
 *    translate it in the user interface.
 *  - controllers: An array specifying controller classes that handle various
 *    aspects of the entity type's functionality.
 *  - config_prefix: This tells the config system the prefix to use for
 *    filenames when storing entities. This means that the default entity we
 *    include in our module has the filename
 *    'maestro.templates.Template.yml'.
 *  - entity_keys: Specifies the class variable(s) in which unique keys are
 *    stored for this entity type.
 *
 *
 * @see annotation
 * @see Drupal\Core\Annotation\Translation
 *
 * @ingroup maestro
 *
 * @ConfigEntityType(
 *   id = "maestro_template",
 *   label = @Translation("Maestro Template"),
 *   admin_permission = "administer maestro templates",
 *   handlers = {
 *     "access" = "Drupal\maestro\MaestroTemplateAccessController",
 *     "list_builder" = "Drupal\maestro\Controller\MaestroTemplateListBuilder",
 *     "form" = {
 *       "add" = "Drupal\maestro\Form\MaestroTemplateAddForm",
 *       "edit" = "Drupal\maestro\Form\MaestroTemplateEditForm",
 *       "delete" = "Drupal\maestro\Form\MaestroTemplateDeleteForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "/maestro/manage/{maestro_template}",
 *     "delete-form" = "/maestro/manage/{maestro_template}/delete"
 *   }
 * )
 */
class MaestroTemplate extends ConfigEntityBase {

  /**
   * The Template ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Template UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The Template label.
   *
   * @var string
   */
  public $label;

  /**
   * The Template app group.
   *
   * @var string
   */
  public $app_group;


  /**
   * The Template canvas height.
   *
   * @var string
   */
  public $canvas_height;

  

  /**
   * The Template canvas width.
   *
   * @var string
   */
  public $canvas_width;
  

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
  
    if ($rel == 'config-translation-overview') {
      $uri_route_parameters['is_modal'] = 'notmodal';
    }
  
    return $uri_route_parameters;
  }
}
