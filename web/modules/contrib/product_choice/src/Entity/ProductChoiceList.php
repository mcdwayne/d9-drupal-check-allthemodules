<?php

namespace Drupal\product_choice\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Product choice list entity.
 *
 * @ConfigEntityType(
 *   id = "product_choice_list",
 *   label = @Translation("Product choice list"),
 *   handlers = {
 *     "list_builder" = "Drupal\product_choice\ProductChoiceListListBuilder",
 *     "form" = {
 *       "add" = "Drupal\product_choice\Form\ProductChoiceListForm",
 *       "edit" = "Drupal\product_choice\Form\ProductChoiceListForm",
 *       "delete" = "Drupal\product_choice\Form\ProductChoiceListDeleteForm"
 *     },
 *   },
 *   config_prefix = "product_choice_list",
 *   admin_permission = "administer product choice lists",
 *   bundle_of = "product_choice_term",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "collection" = "/admin/commerce/config/product_choice_lists",
 *     "add-form" = "/admin/commerce/config/product_choice_lists/add",
 *     "terms-list" = "/admin/commerce/config/product_choice_lists/{product_choice_list}/terms",
 *     "canonical" = "/admin/commerce/config/product_choice_lists/{product_choice_list}",
 *     "edit-form" = "/admin/commerce/config/product_choice_lists/{product_choice_list}/edit",
 *     "delete-form" = "/admin/commerce/config/product_choice_lists/{product_choice_list}/delete"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "help_text",
 *     "allowed_formats",
 *   }
 * )
 */
class ProductChoiceList extends ConfigEntityBundleBase implements ProductChoiceListInterface {

  /**
   * The Product choice list ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Product choice list label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Product choice list descripiton.
   *
   * @var string
   */
  protected $description;

  /**
   * The Product choice list help text.
   *
   * @var string
   */
  protected $help_text;

  /**
   * Allowed formats for this list.
   *
   * @var array
   */
  protected $allowed_formats = [];

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
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

  /**
   * {@inheritdoc}
   */
  public function getHelpText() {
    return $this->help_text;
  }

  /**
   * {@inheritdoc}
   */
  public function setHelpText($help_text) {
    $this->help_text = $help_text;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowedFormats() {
    return array_filter($this->allowed_formats);
  }

  /**
   * {@inheritdoc}
   */
  public function hasAllowedFormat($allowed_format) {
    return in_array($allowed_format, $this->allowed_formats);
  }

  /**
   * {@inheritdoc}
   */
  public function addAllowedFormat($allowed_format) {
    if (!$this->hasAllowedFormat($allowed_format)) {
      $this->allowed_formats[] = $allowed_format;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeAllowedFormat($allowed_format) {
    $this->allowed_formats = array_diff($this->allowed_formats, [$allowed_format]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    // Delete all list terms.
    $storage_handler = \Drupal::entityTypeManager()->getStorage('product_choice_term');
    $terms = $storage_handler->loadByProperties(['lid' => array_keys($entities)]);
    $storage_handler->delete($terms);
  }

}
