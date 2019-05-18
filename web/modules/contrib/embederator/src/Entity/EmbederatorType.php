<?php

namespace Drupal\embederator\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\embederator\EmbederatorTypeInterface;

/**
 * Defines the embederator_type entity.
 *
 * A configuration entity used to manage
 * bundles for the embederator entity.
 *
 * @ConfigEntityType(
 *   id = "embederator_type",
 *   label = @Translation("Embederator Type"),
 *   bundle_of = "embederator",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   config_prefix = "embederator_type",
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "use_ssi",
 *     "embed_markup",
 *     "embed_url",
 *     "wrapper_class"
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\embederator\Entity\Controller\EmbederatorTypeListBuilder",
 *     "form" = {
 *       "default" = "Drupal\embederator\Form\EmbederatorTypeForm",
 *       "add" = "Drupal\embederator\Form\EmbederatorTypeForm",
 *       "edit" = "Drupal\embederator\Form\EmbederatorTypeForm",
 *       "delete" = "Drupal\embederator\Form\EmbederatorTypeDeleteForm",
 *     }
 *   },
 *   admin_permission = "administer embederator types",
 *   links = {
 *     "add-form" = "/admin/structure/embederator_type/add",
 *     "edit-form" = "/admin/structure/embederator_type/{embederator_type}/edit",
 *     "delete-form" = "/admin/structure/embederator_type/{embederator_type}/delete",
 *     "collection" = "/admin/structure/embederator_type",
 *   }
 * )
 */
class EmbederatorType extends ConfigEntityBundleBase implements EmbederatorTypeInterface {
  /**
   * The machine name of the practical type.
   *
   * @var string
   */
  protected $id;
  /**
   * The human-readable name of the practical type.
   *
   * @var string
   */
  protected $label;


  /**
   * A brief description of the practical type.
   *
   * @var string
   */
  protected $description;

  /**
   * Whether this is a pure embed or a server-side include from URL.
   *
   * @var bool
   */
  protected $use_ssi;

  /**
   * Markup skeleton for the embed.
   *
   * @var string
   */
  protected $embed_markup;

  /**
   * Embed URL, for SSI.
   *
   * @var string
   */
  protected $embed_url;

  /**
   * Wrapper class.
   *
   * @var string
   */
  protected $wrapper_class;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
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
   * Server-side include?
   */
  public function getUseSsi() {
    return $this->use_ssi;
  }

  /**
   * Set true for server-side include.
   */
  public function setUseSsi($use_ssi) {
    $this->use_ssi = $use_ssi;
    return $this;
  }

  /**
   * Retrieve the markup field.
   */
  public function getMarkup() {
    return $this->embed_markup;
  }

  /**
   * Retrieve the markup HTML (value).
   */
  public function getMarkupHtml() {
    return $this->getMarkup()['value'];
  }

  /**
   * Retrieve the markup format.
   */
  public function getMarkupFormat() {
    return $this->getMarkup()['format'];
  }

  /**
   * Set the markup value and format.
   */
  public function setMarkup($value, $format) {
    $this->embed_markup->value = $value;
    $this->embed_markup->format = $format;
    return $this;
  }

  /**
   * Get the SSI URL.
   */
  public function getEmbedUrl() {
    return $this->embed_url;
  }

  /**
   * Set the SSI URL.
   */
  public function setEmbedUrl($url) {
    $this->embed_url = $url;
    return $this;
  }

  /**
   * Retrieve the wrapper class.
   */
  public function getWrapperClass() {
    return $this->wrapper_class;
  }

  /**
   * Retrieve the wrapper class.
   */
  public function setWrapperClass($class) {
    $this->wrapper_class = $class;
    return $this;
  }

}
