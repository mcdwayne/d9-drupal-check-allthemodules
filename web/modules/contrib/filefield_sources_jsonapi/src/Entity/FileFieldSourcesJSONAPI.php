<?php

namespace Drupal\filefield_sources_jsonapi\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\filefield_sources_jsonapi\FileFieldSourcesJSONAPIInterface;

/**
 * Defines JSON API settings for File field sources.
 *
 * @ConfigEntityType(
 *   id = "filefield_sources_jsonapi",
 *   label = @Translation("File field sources JSON API settings"),
 *   handlers = {
 *     "list_builder" = "Drupal\filefield_sources_jsonapi\FileFieldSourcesJSONAPIListBuilder",
 *     "form" = {
 *       "add" = "Drupal\filefield_sources_jsonapi\Form\FileFieldSourcesJSONAPIForm",
 *       "edit" = "Drupal\filefield_sources_jsonapi\Form\FileFieldSourcesJSONAPIForm",
 *       "delete" = "Drupal\filefield_sources_jsonapi\Form\FileFieldSourcesJSONAPIDeleteForm"
 *     }
 *   },
 *   config_prefix = "filefield_sources_jsonapi",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/services/filefield_sources_jsonapi/{filefield_sources_jsonapi}",
 *     "edit-form" = "/admin/config/services/filefield_sources_jsonapi/{filefield_sources_jsonapi}/edit",
 *     "delete-form" = "/admin/config/services/filefield_sources_jsonapi/{filefield_sources_jsonapi}/delete",
 *     "collection" = "/admin/config/services/filefield_sources_jsonapi"
 *   }
 * )
 */
class FileFieldSourcesJSONAPI extends ConfigEntityBase implements FileFieldSourcesJSONAPIInterface {

  const REMOTE_JSONAPI_LISTER_SORT = '-created|Newest first';
  const REMOTE_JSONAPI_LISTER_ITEM_NUM = 12;

  /**
   * The settings ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Label of the settings.
   *
   * @var string
   */
  protected $label;

  protected $apiUrl = NULL;

  protected $params = '';

  protected $urlAttributePath = NULL;

  protected $thumbnailUrlAttributePath = NULL;

  protected $titleAttributePath = NULL;

  protected $altAttributePath = NULL;

  protected $sortOptionList = self::REMOTE_JSONAPI_LISTER_SORT;

  protected $searchFilter = NULL;

  protected $itemsPerPage = self::REMOTE_JSONAPI_LISTER_ITEM_NUM;

  protected $basicAuthentication = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getApiUrl() {
    return $this->apiUrl;
  }

  /**
   * {@inheritdoc}
   */
  public function getParams() {
    return $this->params;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlAttributePath() {
    return $this->urlAttributePath;
  }

  /**
   * {@inheritdoc}
   */
  public function getThumbnailUrlAttributePath() {
    return $this->thumbnailUrlAttributePath;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitleAttributePath() {
    return $this->titleAttributePath;
  }

  /**
   * {@inheritdoc}
   */
  public function getAltAttributePath() {
    return $this->altAttributePath;
  }

  /**
   * {@inheritdoc}
   */
  public function getSortOptionList() {
    return $this->sortOptionList;
  }

  /**
   * {@inheritdoc}
   */
  public function getSearchFilter() {
    return $this->searchFilter;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemsPerPage() {
    return $this->itemsPerPage;
  }

  /**
   * {@inheritdoc}
   */
  public function getBasicAuthentication() {
    return $this->basicAuthentication;
  }

  /**
   * Load entities to option list.
   */
  public static function getSettingsOptionList($ids = NULL) {
    $entities = self::loadMultiple($ids);
    $return = NULL;
    foreach ($entities as $id => $entity) {
      $return[$id] = $entity->label();
    }

    return $return;
  }

}
