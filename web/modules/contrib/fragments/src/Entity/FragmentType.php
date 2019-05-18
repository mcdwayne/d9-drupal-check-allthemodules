<?php

namespace Drupal\fragments\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityDescriptionInterface;

/**
 * Defines the fragment type entity.
 *
 * @ConfigEntityType(
 *   id = "fragment_type",
 *   label = @Translation("Fragment type"),
 *   label_collection = @Translation("Fragment types"),
 *   label_singular = @Translation("fragment type"),
 *   label_plural = @Translation("fragment types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count fragment type",
 *     plural = "@count fragment types",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\fragments\FragmentTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\fragments\Form\FragmentTypeForm",
 *       "edit" = "Drupal\fragments\Form\FragmentTypeForm",
 *       "delete" = "Drupal\fragments\Form\FragmentTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "fragment_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "fragment",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/fragment-types/{fragment_type}",
 *     "add-form" = "/admin/structure/fragment-types/add",
 *     "edit-form" = "/admin/structure/fragment-types/{fragment_type}/edit",
 *     "delete-form" = "/admin/structure/fragment-types/{fragment_type}/delete",
 *     "auto-label" = "/admin/structure/fragment-types/{fragment_type}/auto-label",
 *     "collection" = "/admin/structure/fragment-types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description"
 *   }
 * )
 */
class FragmentType extends ConfigEntityBundleBase implements FragmentTypeInterface, EntityDescriptionInterface {

  /**
   * The fragment type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The fragment type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The fragment type description.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
  }

}
