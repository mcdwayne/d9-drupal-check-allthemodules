<?php

namespace Drupal\lunr\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Url;
use Drupal\lunr\LunrSearchInterface;

/**
 * Defines the Lunr search entity.
 *
 * @ConfigEntityType(
 *   id = "lunr_search",
 *   label = @Translation("Lunr search"),
 *   label_collection = @Translation("Lunr searches"),
 *   label_singular = @Translation("Lunr search"),
 *   label_plural = @Translation("Lunr searches"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Lunr search",
 *     plural = "@count Lunr searches",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "list_builder" = "Drupal\lunr\LunrSearchListBuilder",
 *     "form" = {
 *       "add" = "Drupal\lunr\Form\LunrSearchEditForm",
 *       "edit" = "Drupal\lunr\Form\LunrSearchEditForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer lunr search",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "canonical" = "/lunr_search/{lunr_search}",
 *     "delete-form" = "/admin/config/lunr_search/{lunr_search}/delete",
 *     "edit-form" = "/admin/config/lunr_search/{lunr_search}",
 *     "index" = "/admin/config/lunr_search/{lunr_search}/index",
 *     "collection" = "/admin/config/lunr_search",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "path",
 *     "view_id",
 *     "view_display_id",
 *     "index_fields",
 *     "display_field",
 *     "results_per_page"
 *   }
 * )
 */
class LunrSearch extends ConfigEntityBase implements LunrSearchInterface {

  /**
   * The search ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The label.
   *
   * @var string
   */
  protected $label;

  /**
   * The path.
   *
   * @var string
   */
  protected $path;

  /**
   * The view ID.
   *
   * @var string
   */
  protected $view_id;

  /**
   * The view display ID.
   *
   * @var string
   */
  protected $view_display_id;

  /**
   * The index fields.
   *
   * @var array
   */
  protected $index_fields;

  /**
   * The display field.
   *
   * @var string
   */
  protected $display_field;

  /**
   * The number of search results per page.
   *
   * @var int
   */
  protected $results_per_page;

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewId() {
    return $this->view_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewDisplayId() {
    return $this->view_display_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexFields() {
    return $this->index_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayField() {
    return $this->display_field;
  }

  /**
   * {@inheritdoc}
   */
  public function getResultsPerPage() {
    return (int) $this->results_per_page;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastIndexTime() {
    return \Drupal::state()->get('lunr.last_index_time.' . $this->id(), time());
  }

  /**
   * {@inheritdoc}
   */
  public function setPath($path) {
    $this->path = $path;
  }

  /**
   * {@inheritdoc}
   */
  public function setViewId($view_id) {
    $this->view_id = $view_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setViewDisplayId($view_display_id) {
    $this->view_display_id = $view_display_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setIndexFields(array $fields) {
    $this->index_fields = $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function setDisplayField($field) {
    $this->display_field = $field;
  }

  /**
   * {@inheritdoc}
   */
  public function setResultsPerPage($number) {
    $this->results_per_page = $number;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastIndexTime($timestamp) {
    \Drupal::state()->set('lunr.last_index_time.' . $this->id(), $timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function getView() {
    /** @var \Drupal\views\ViewEntityInterface $view */
    if ($this->getViewId() && $this->getViewDisplayId() && $view = $this->viewStorage()->load($this->getViewId())) {
      $executable = $view->getExecutable();
      if (!$executable->setDisplay($this->getViewDisplayId())) {
        return FALSE;
      }
      return $executable;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexPath() {
    $langcode = $this->languageManager()->getCurrentLanguage()->getId();
    return 'public://lunr_search/' . $this->id() . '/' . $langcode . '/index.json';
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseIndexPath() {
    return 'public://lunr_search/' . $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getDocumentPathPattern() {
    $langcode = $this->languageManager()->getCurrentLanguage()->getId();
    return 'public://lunr_search/' . $this->id() . '/' . $langcode . '/document_PAGE.json';
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $this->addDependency('module', 'views');
    if ($view_id = $this->getViewId()) {
      $this->addDependency('config', "views.view.$view_id");
    }
    return $this;
  }

  /**
   * Wraps the view storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The view storage.
   */
  protected function viewStorage() {
    return \Drupal::entityTypeManager()->getStorage('view');
  }

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'edit-form', array $options = []) {
    if ($rel === 'canonical' && $this->getPath()) {
      return Url::fromRoute('lunr_search.' . $this->id());
    }
    return parent::toUrl($rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    parent::delete();
    \Drupal::service('state')->delete('lunr.last_index_time.' . $this->id());
    if (file_exists($this->getBaseIndexPath())) {
      file_unmanaged_delete_recursive($this->getBaseIndexPath());
    }
  }

}
