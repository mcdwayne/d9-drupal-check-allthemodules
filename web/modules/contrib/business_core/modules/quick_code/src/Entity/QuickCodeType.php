<?php

namespace Drupal\quick_code\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\quick_code\QuickCodeTypeInterface;

/**
 * Defines the quick_code_type entity.
 *
 * @ConfigEntityType(
 *   id = "quick_code_type",
 *   label = @Translation("Quick code type"),
 *   label_collection = @Translation("Quick code types"),
 *   handlers = {
 *     "view_builder" = "Drupal\quick_code\QuickCodeTypeViewBuilder",
 *     "list_builder" = "Drupal\quick_code\QuickCodeTypeListBuilder",
 *     "form" = {
 *       "default" = "Drupal\quick_code\QuickCodeTypeForm",
 *       "delete" = "Drupal\quick_code\Form\QuickCodeTypeDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer quick codes",
 *   config_prefix = "type",
 *   bundle_of = "quick_code",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight"
 *   },
 *   links = {
 *     "add-form" = "/admin/quick_code_type/add",
 *     "delete-form" = "/admin/quick_code_type/{quick_code_type}/delete",
 *     "canonical" = "/admin/quick_code_type/{quick_code_type}",
 *     "edit-form" = "/admin/quick_code_type/{quick_code_type}/edit",
 *     "collection" = "/admin/quick_code_type",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "hierarchy",
 *     "code",
 *     "encoding_rules",
 *     "weight",
 *   }
 * )
 */
class QuickCodeType extends ConfigEntityBundleBase implements QuickCodeTypeInterface {

  /**
   * The quick code type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Name of the quick code type.
   *
   * @var string
   */
  protected $label;

  /**
   * Description of the quick code type.
   *
   * @var string
   */
  protected $description;

  /**
   * @var bool
   */
  protected $code = TRUE;

  /**
   * @var string
   */
  protected $encoding_rules = '[code:rules:01]';

  /**
   * @var bool
   */
  protected $hierarchy = TRUE;

  /**
   * The weight of this quick code type in relation to other quick code types.
   * @var int
   */
  protected $weight = 0;

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get('quick_code.type.locked');
    return isset($locked[$this->id()]) ? $locked[$this->id()] : FALSE;
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
  public function getCode() {
    return $this->code;
  }

  /**
   * {@inheritdoc}
   */
  public function getEncodingRules() {
    return $this->encoding_rules;
  }

  /**
   * {@inheritdoc}
   */
  public function getHierarchy() {
    return $this->hierarchy;
  }

}
