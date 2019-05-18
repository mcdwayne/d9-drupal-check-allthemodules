<?php

namespace Drupal\bigvideo\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the BigVideo Page entity.
 *
 * @ConfigEntityType(
 *   id = "bigvideo_page",
 *   label = @Translation("BigVideo Page"),
 *   handlers = {
 *     "list_builder" = "Drupal\bigvideo\BigvideoPageListBuilder",
 *     "form" = {
 *       "add" = "Drupal\bigvideo\Form\BigvideoPageForm",
 *       "edit" = "Drupal\bigvideo\Form\BigvideoPageForm",
 *       "delete" = "Drupal\bigvideo\Form\BigvideoPageDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\bigvideo\BigvideoPageHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "bigvideo_page",
 *   admin_permission = "administer bigvideo",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/user-interface/bigvideo/pages/{bigvideo_page}",
 *     "add-form" = "/admin/config/user-interface/bigvideo/pages/add",
 *     "edit-form" = "/admin/config/user-interface/bigvideo/pages/{bigvideo_page}/edit",
 *     "delete-form" = "/admin/config/user-interface/bigvideo/pages/{bigvideo_page}/delete",
 *     "collection" = "/admin/config/user-interface/bigvideo/pages"
 *   }
 * )
 */
class BigvideoPage extends ConfigEntityBase implements BigvideoPageInterface {

  /**
   * The BigVideo Page ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The BigVideo Page label.
   *
   * @var string
   */
  protected $label;

  /**
   * The BigVideo Page source identifier.
   *
   * @var int
   */
  protected $source;

  /**
   * The BigVideo Page path.
   *
   * @var string
   */
  protected $path;

  /**
   * The BigVideo Page selector.
   *
   * @var string
   */
  protected $selector = '';

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * {@inheritdoc}
   */
  public function setSource($source) {
    $this->source = $source;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function setPath($path) {
    $this->path = $path;
    return $this;
  }

  /**
   * Get page selector.
   *
   * @return string
   *   Page selector.
   */
  public function getSelector() {
    return $this->selector;
  }

  /**
   * Set page selector.
   *
   * @param string $selector
   *   New page selector.
   *
   * @return $this
   */
  public function setSelector($selector) {
    $this->selector = $selector;
    return $this;
  }

}
