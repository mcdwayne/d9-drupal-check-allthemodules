<?php

namespace Drupal\custom_header_image\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Header image entity.
 *
 * @ConfigEntityType(
 *   id = "header_image",
 *   label = @Translation("Header image"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\custom_header_image\HeaderImageListBuilder",
 *     "form" = {
 *       "add" = "Drupal\custom_header_image\Form\HeaderImageForm",
 *       "edit" = "Drupal\custom_header_image\Form\HeaderImageForm",
 *       "delete" = "Drupal\custom_header_image\Form\HeaderImageDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\custom_header_image\HeaderImageHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "header_image",
 *   admin_permission = "administer header image",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/header_image/{header_image}",
 *     "add-form" = "/admin/structure/header_image/add",
 *     "edit-form" = "/admin/structure/header_image/{header_image}/edit",
 *     "delete-form" = "/admin/structure/header_image/{header_image}/delete",
 *     "collection" = "/admin/structure/header_image"
 *   }
 * )
 */
class HeaderImage extends ConfigEntityBase implements HeaderImageInterface {

  /**
   * The Header image ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Header image label.
   *
   * @var string
   */
  protected $label;

  /**
   * The associated file id.
   *
   * @var int
   */
  protected $header_image;

  /**
   * The entity id of the associated image style.
   *
   * @var array
   */
  protected $image_styles;

  /**
   * The alt text of the image.
   *
   * @var string
   */
  protected $alt;

  /**
   * An array of acceptable paths for this header image.
   *
   * @var string[]
   */
  protected $paths = [];

  /**
   * The responsive sizes for src switching.
   *
   * @var string[]
   */
  protected $sizes = [];

  /**
   * {@inheritdoc}
   */
  public function hasHeaderImage() {
    return (bool) $this->header_image;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeaderImageId() {
    return $this->header_image;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeaderImage() {
    return $this->entityTypeManager()->getStorage('file')->load($this->header_image);
  }

  /**
   * {@inheritdoc}
   */
  public function getImageStyleId() {
    return reset($this->image_styles);
  }

  /**
   * {@inheritdoc}
   */
  public function getImageStyleIds() {
    return $this->image_styles;
  }

  /**
   * {@inheritdoc}
   */
  public function hasImageStyle() {
    return (bool) $this->image_styles;
  }

  /**
   * {@inheritdoc}
   */
  public function getImageStyles() {
    $styles = [];
    foreach ($this->image_styles as $image_style) {
      $styles[] = $this->entityTypeManager()->getStorage('image_style')->load($image_style);
    }
    return $styles;
  }

  /**
   * {@inheritdoc}
   */
  public function hasAltText() {
    return (bool) $this->alt;
  }

  /**
   * {@inheritdoc}
   */
  public function getAltText() {
    return $this->hasAltText() ? $this->alt : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getPaths() {
    return $this->paths;
  }

  /**
   * {@inheritdoc}
   */
  public function getPathsString() {
    return implode("\n", $this->paths);
  }

  public function hasSizes() {
    return (bool) $this->sizes;
  }

  public function getSizes() {
    return $this->sizes;
  }


  /**
   * {@inheritdoc}
   */
  public function save() {
    $file = $this->getHeaderImage();
    if ($file->isTemporary()) {
      /** @var \Drupal\file\FileUsage\FileUsageInterface $usage */
      $usage = \Drupal::service('file.usage');
      $usage->add($file, 'custom_header_image', 'header_image', $this->id());
    }
    return parent::save();
  }

}
