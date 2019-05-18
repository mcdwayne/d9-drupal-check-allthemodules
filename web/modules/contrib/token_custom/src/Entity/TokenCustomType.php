<?php

namespace Drupal\token_custom\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\token_custom\TokenCustomTypeInterface;

/**
 * Defines the Custom Token Type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "token_custom_type",
 *   label = @Translation("Custom Token Type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\token_custom\Form\TokenCustomTypeForm",
 *       "edit" = "Drupal\token_custom\Form\TokenCustomTypeForm",
 *       "default" = "Drupal\token_custom\Form\TokenCustomTypeForm",
 *       "delete" = "Drupal\token_custom\Form\TokenCustomTypeDeleteForm"
 *     },
 *     "list_builder" = "Drupal\token_custom\TokenCustomTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer custom token types",
 *   config_prefix = "type",
 *   bundle_of = "token_custom",
 *   revisionable = FALSE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "machineName",
 *     "label" = "name"
 *   },
 *   config_export = {
 *     "machineName",
 *     "name",
 *     "description",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/token-custom/type/add",
 *     "edit-form" = "/admin/structure/token-custom/manage/{token_custom}/edit",
 *     "delete-form" = "/admin/structure/token-custom/manage/{token_custom}/delete",
 *     "collection" = "/admin/structure/token-custom/type",
 *   }
 * )
 */
class TokenCustomType extends ConfigEntityBundleBase implements TokenCustomTypeInterface {

  /**
   * The machine name of this media bundle.
   *
   * @var string
   */
  public $machineName;

  /**
   * The human-readable name of the media bundle.
   *
   * @var string
   */
  public $name;

  /**
   * A brief description of this media bundle.
   *
   * @var string
   */
  public $description;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->machineName;
  }

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
    return $this;
  }

}
