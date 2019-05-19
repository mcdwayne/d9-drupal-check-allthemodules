<?php
namespace Drupal\x_reference\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the x_reference_type entity.
 *
 * @ConfigEntityType(
 *   id = "x_reference_type",
 *   label = @Translation("X-reference type"),
 *   handlers = {
 *     "list_builder" = "Drupal\x_reference\Entity\Controller\XReferenceTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\x_reference\Form\XReferenceTypeForm",
 *       "edit" = "Drupal\x_reference\Form\XReferenceTypeForm",
 *       "delete" = "Drupal\x_reference\Form\XReferenceTypeDeleteForm",
 *     },
 *    "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     }
 *   },
 *   bundle_of = "x_reference",
 *   entity_keys = {
 *     "label" = "name",
 *     "id" = "machine_name",
 *   },
 *   admin_permission = "administer x_reference_type",
 *   links = {
 *     "edit-form" = "/admin/config/system/x_reference_type/{x_reference_type}",
 *     "delete-form" = "/admin/config/system/x_reference_type/{x_reference_type}/delete",
 *   },
 *   config_export = {
 *     "name",
 *     "machine_name",
 *     "source_entity_source",
 *     "source_entity_type",
 *     "target_entity_source",
 *     "target_entity_type",
 *   }
 * )
 */
class XReferenceType extends ConfigEntityBundleBase  {

  const ENTITY_TYPE = 'x_reference_type';

  const ENTITY_SOURCE_DRUPAL = 'drupal';

  /** @var string */
  public $name;

  /** @var string */
  public $machine_name;

  /** @var string */
  public $source_entity_source;

  /** @var string */
  public $source_entity_type;

  /** @var string */
  public $target_entity_source;

  // @todo: possibly it should support multiple sources/entity types.
  /** @var string */
  public $target_entity_type;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->{$this->getEntityType()->getKey('id')};
  }

}
