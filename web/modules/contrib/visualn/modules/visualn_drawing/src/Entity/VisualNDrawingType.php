<?php

namespace Drupal\visualn_drawing\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the VisualN Drawing type entity.
 *
 * @ConfigEntityType(
 *   id = "visualn_drawing_type",
 *   label = @Translation("VisualN Drawing type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\visualn_drawing\VisualNDrawingTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\visualn_drawing\Form\VisualNDrawingTypeForm",
 *       "edit" = "Drupal\visualn_drawing\Form\VisualNDrawingTypeForm",
 *       "delete" = "Drupal\visualn_drawing\Form\VisualNDrawingTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\visualn_drawing\VisualNDrawingTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "visualn_drawing_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "visualn_drawing",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "drawing_fetcher_field" = "drawing_fetcher_field"
 *   },
 *   links = {
 *     "canonical" = "/admin/visualn/drawing-types/{visualn_drawing_type}",
 *     "add-form" = "/admin/visualn/drawing-types/add",
 *     "edit-form" = "/admin/visualn/drawing-types/{visualn_drawing_type}/edit",
 *     "delete-form" = "/admin/visualn/drawing-types/{visualn_drawing_type}/delete",
 *     "collection" = "/admin/visualn/drawing-types"
 *   }
 * )
 */
class VisualNDrawingType extends ConfigEntityBundleBase implements VisualNDrawingTypeInterface {

  /**
   * The VisualN Drawing type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The VisualN Drawing type label.
   *
   * @var string
   */
  protected $label;

  /**
   * Default value of the 'Create new revision' checkbox of this drawing type.
   *
   * @var bool
   */
  protected $new_revision = TRUE;

  /**
   * The VisualN Drawing Fetcher field ID.
   *
   * @var string
   */
  protected $drawing_fetcher_field;

  /**
   * {@inheritdoc}
   */
  public function getDrawingFetcherField() {
    return $this->drawing_fetcher_field;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldCreateNewRevision() {
    // @todo: the method is used instead of isNewRevision()
    return $this->new_revision;
  }

  /**
   * {@inheritdoc}
   */
  public function setNewRevision($new_revision) {
    $this->new_revision = $new_revision;
  }

}
