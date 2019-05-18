<?php

namespace Drupal\pardot\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Pardot Contact Form Mapping config entity.
 *
 * @ingroup Pardot
 *
 * @ConfigEntityType(
 *   id = "pardot_contact_form_map",
 *   label = @Translation("Pardot Contact Form Map"),
 *   admin_permission = "administer pardot settings",
 *   handlers = {
 *     "access" = "Drupal\pardot\PardotContactFormMapAccessController",
 *     "list_builder" = "Drupal\pardot\Controller\PardotContactFormMapListBuilder",
 *     "form" = {
 *       "add" = "Drupal\pardot\Form\PardotContactFormMapAddForm",
 *       "edit" = "Drupal\pardot\Form\PardotContactFormMapEditForm",
 *       "delete" = "Drupal\pardot\Form\PardotContactFormMapDeleteForm",
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/services/pardot/campaigns/{pardot_contact_form_map}",
 *     "delete-form" = "/admin/config/services/pardot/campaigns/{pardot_contact_form_map}/delete"
 *   }
 * )
 */
class PardotContactFormMap extends ConfigEntityBase {

  /**
   * The Pardot Campaign ID.
   *
   * @var string
   */
  public $id;

  /**
   * The campaign UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The Pardot Campaign label.
   *
   * @var string
   */
  public $label;

  /**
   * The Contact Form Mapping status.
   *
   * @var boolean
   */
  public $status;

  /**
   * The Pardot Form Handler Post Url.
   *
   * @var boolean
   */
  public $post_url;

  /**
   * The Contact Form ID.
   *
   * @var string
   */
  public $contact_form_id;

  /**
   * The contact form mapping.
   *
   * @var array
   */
  public $mapped_fields = [];
}
