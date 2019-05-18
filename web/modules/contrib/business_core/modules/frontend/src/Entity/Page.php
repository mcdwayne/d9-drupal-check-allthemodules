<?php

namespace Drupal\frontend\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\frontend\PageInterface;

/**
 * Defines the configured page entity.
 *
 * @ConfigEntityType(
 *   id = "page",
 *   label = @Translation("Page"),
 *   label_collection = @Translation("Pages"),
 *   handlers = {
 *     "list_builder" = "Drupal\frontend\PageListBuilder",
 *     "form" = {
 *       "default" = "Drupal\frontend\PageForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer pages",
 *   config_prefix = "page",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/page/add",
 *     "delete-form" = "/admin/page/{page}/delete",
 *     "canonical" = "/admin/page/{page}",
 *     "edit-form" = "/admin/page/{page}/edit",
 *     "collection" = "/admin/page",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "layout",
 *     "path",
 *     "components",
 *   },
 * )
 */
class Page extends Container implements PageInterface {

  /**
   * @var string
   */
  protected $layout;

  /**
   * @var string
   */
  protected $path;

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    $locked = \Drupal::state()->get('frontend.page.locked');
    return isset($locked[$this->id()]) ? $locked[$this->id()] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLayout() {
    return $this->layout;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->path;
  }

}
