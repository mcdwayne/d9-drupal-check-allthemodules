<?php

namespace Drupal\wizenoze\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\wizenoze\WizenozePageInterface;

/**
 * Defines the Search page entity.
 *
 * @ConfigEntityType(
 *   id = "wizenoze",
 *   label = @Translation("Wizenoze page"),
 *   handlers = {
 *     "list_builder" = "Drupal\wizenoze\WizenozePageListBuilder",
 *     "form" = {
 *       "add" = "Drupal\wizenoze\Form\WizenozePageForm",
 *       "edit" = "Drupal\wizenoze\Form\WizenozePageForm",
 *       "delete" = "Drupal\wizenoze\Form\WizenozePageDeleteForm"
 *     }
 *   },
 *   config_prefix = "wizenoze",
 *   admin_permission = "administer wizenoze",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/wizenoze/wizenoze-pages/{wizenoze}",
 *     "edit-form" = "/admin/config/wizenoze/wizenoze-pages/{wizenoze}/edit",
 *     "delete-form" = "/admin/config/wizenoze/wizenoze-pages/{wizenoze}/delete",
 *     "collection" = "/admin/config/wizenoze/wizenoze-pages"
 *   }
 * )
 */
class Wizenoze extends ConfigEntityBase implements WizenozePageInterface {

  /**
   * The Search page ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Search page label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Search page path.
   *
   * @var string
   */
  protected $path;

  /**
   * Whether to use clean URLs or not.
   *
   * @var bool
   */
  protected $clean_url = TRUE;

  /**
   * Whether to show all resluts when no search is performed.
   *
   * @var bool
   */
  protected $show_all_when_no_keys = FALSE;

  /**
   * The Search Api index.
   *
   * @var string
   */
  protected $index;

  /**
   * The limit per page.
   *
   * @var string
   */
  protected $limit = 10;

  /**
   * Whether to show the search form above search results.
   *
   * @var bool
   */
  protected $show_search_form = TRUE;

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
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
  public function getCleanUrl() {
    return $this->clean_url;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndex() {
    return $this->index;
  }

  /**
   * {@inheritdoc}
   */
  public function getLimit() {
    return $this->limit;
  }

  /**
   * {@inheritdoc}
   */
  public function showSearchForm() {
    return $this->show_search_form;
  }

  /**
   * {@inheritdoc}
   */
  public function showAllResultsWhenNoSearchIsPerformed() {
    return $this->show_all_when_no_keys;
  }

}
