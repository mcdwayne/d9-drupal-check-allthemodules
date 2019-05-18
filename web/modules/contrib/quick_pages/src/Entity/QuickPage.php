<?php

/**
 * @file
 * Contains Drupal\quick_pages\Entity\QuickPage.
 */

namespace Drupal\quick_pages\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\quick_pages\QuickPageInterface;

/**
 * Defines the quick page entity type.
 *
 * @ConfigEntityType(
 *   id = "quick_page",
 *   label = @Translation("Quick page"),
 *   handlers = {
 *     "list_builder" = "Drupal\quick_pages\Controller\QuickPageListBuilder",
 *     "form" = {
 *       "add" = "Drupal\quick_pages\Form\QuickPageForm",
 *       "edit" = "Drupal\quick_pages\Form\QuickPageForm",
 *       "delete" = "Drupal\quick_pages\Form\QuickPageDeleteForm"
 *     }
 *   },
 *   config_prefix = "quick_page",
 *   admin_permission = "administer quick_page",
 *   links = {
 *     "collection" = "/admin/structure/quick-page",
 *     "add-form" = "/admin/structure/quick-page/add",
 *     "edit-form" = "/admin/structure/quick-page/{quick_page}",
 *     "delete-form" = "/admin/structure/quick-page/{quick_page}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class QuickPage extends ConfigEntityBase implements QuickPageInterface {

  /**
   * The ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The administrative label.
   *
   * @var string
   */
  protected $label;

  /**
   * The status.
   *
   * @var boolean
   */
  protected $status = TRUE;

  /**
   * The quick page path.
   *
   * @var string
   */
  protected $path;

  /**
   * The $title.
   *
   * @var string
   */
  protected $title;

  /**
   * The theme.
   *
   * @var string
   */
  protected $theme;

  /**
   * The display variant.
   *
   * @var array
   */
  protected $displayVariant;

  /**
   * The main content provider.
   *
   * @var array
   */
  protected $mainContent;

  /**
   * The route access configuration.
   *
   * @var array
   */
  protected $access;

}
