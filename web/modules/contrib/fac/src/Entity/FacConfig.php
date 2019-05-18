<?php

namespace Drupal\fac\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\fac\FacConfigInterface;
use Drupal\Core\StreamWrapper\PublicStream;

/**
 * Defines the FacConfig entity.
 *
 * @ConfigEntityType(
 *   id = "fac_config",
 *   label = @Translation("Fast Autocomplete configuration"),
 *   label_collection = @Translation("Fast Autocomplete configurations"),
 *   label_singular = @Translation("Fast Autocomplete configuration"),
 *   label_plural = @Translation("Fast Autocomplete configurations"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Fast Autocomplete configuration",
 *     plural = "@count Fast Autocomplete configurations",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\fac\Controller\FacConfigListBuilder",
 *     "form" = {
 *       "default" = "Drupal\fac\Form\FacConfigForm",
 *       "add" = "Drupal\fac\Form\FacConfigForm",
 *       "edit" = "Drupal\fac\Form\FacConfigForm",
 *       "delete" = "Drupal\fac\Form\FacConfigDeleteForm",
 *       "disable" = "Drupal\fac\Form\FacConfigDisableConfirmForm",
 *       "enable" = "Drupal\fac\Form\FacConfigEnableConfirmForm",
 *     }
 *   },
 *   config_prefix = "fac_config",
 *   admin_permission = "administer fac settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/searchfac/fac_config}",
 *     "delete-form" = "/admin/config/search/fac/{fac_config}/delete",
 *     "disable" = "/admin/config/search/fac/{fac_config}/disable",
 *     "enable" = "/admin/config/search/fac/fac_config}/enable",
 *   }
 * )
 */
class FacConfig extends ConfigEntityBase implements FacConfigInterface {

  /**
   * The FacConfig ID.
   *
   * @var string
   */
  public $id;

  /**
   * The FacConfig label.
   *
   * @var string
   */
  public $label;

  /**
   * The FacConfig Search Plugin ID.
   *
   * @var string
   */
  protected $searchPluginId;

  /**
   * The FacConfig Search Plugin configuration.
   *
   * @var string
   */
  protected $searchPluginConfig;

  /**
   * The FacConfig input selectors.
   *
   * @var string
   */
  protected $inputSelectors;

  /**
   * The FacConfig number of results.
   *
   * @var int
   */
  protected $numberOfResults;

  /**
   * The FacConfig empty result.
   *
   * @var string
   */
  protected $emptyResult;

  /**
   * The FacConfig view mode.
   *
   * @var string
   */
  protected $viewModes;

  /**
   * The FacConfig minimum key length.
   *
   * @var int
   */
  protected $keyMinLength;

  /**
   * The FacConfig maximum key length.
   *
   * @var int
   */
  protected $keyMaxLength;

  /**
   * The FacConfig show all results link.
   *
   * @var bool
   */
  protected $allResultsLink;

  /**
   * The FacConfig all results link threshold.
   *
   * @var int
   */
  protected $allResultsLinkThreshold;

  /**
   * The FacConfig breakpoint.
   *
   * @var int
   */
  protected $breakpoint;

  /**
   * The FacConfig result location.
   *
   * @var string
   */
  protected $resultLocation;

  /**
   * The FacConfig highlighting enabled setting.
   *
   * @var bool
   */
  protected $highlightingEnabled;

  /**
   * The FacConfig anonymous search setting.
   *
   * @var bool
   */
  protected $anonymousSearch;

  /**
   * The FacConfig clean up files.
   *
   * @var bool
   */
  protected $cleanUpFiles;

  /**
   * The FacConfig files expiry time.
   *
   * @var string
   */
  protected $filesExpiryTime;

  /**
   * {@inheritdoc}
   */
  public function getSearchPluginId() {
    return $this->searchPluginId;
  }

  /**
   * {@inheritdoc}
   */
  public function getSearchPluginConfig() {
    $config = json_decode($this->searchPluginConfig, TRUE);
    if (empty($config)) {
      $config = [];
    }
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getInputSelectors() {
    return $this->inputSelectors;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberOfResults() {
    return $this->numberOfResults;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmptyResult() {
    return $this->emptyResult;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewModes() {
    return $this->viewModes;
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyMinLength() {
    return $this->keyMinLength;
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyMaxLength() {
    return $this->keyMaxLength;
  }

  /**
   * {@inheritdoc}
   */
  public function showAllResultsLink() {
    return $this->allResultsLink;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllResultsLinkThreshold() {
    return $this->allResultsLinkThreshold;
  }

  /**
   * {@inheritdoc}
   */
  public function getBreakpoint() {
    return $this->breakpoint;
  }

  /**
   * {@inheritdoc}
   */
  public function getResultLocation() {
    return $this->resultLocation;
  }

  /**
   * {@inheritdoc}
   */
  public function highlightingEnabled() {
    return $this->highlightingEnabled;
  }

  /**
   * {@inheritdoc}
   */
  public function anonymousSearch() {
    return $this->anonymousSearch;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanUpFiles() {
    return $this->cleanUpFiles;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilesExpiryTime() {
    return $this->filesExpiryTime;
  }

  /**
   * Deletes Fast Autocomplete configuration JSON files.
   *
   * @param string $expiry_time
   *   The expiry time for the files.
   */
  public function deleteFiles($expiry_time = NULL) {
    if (empty($expiry_time)) {
      // No date and time given so just delete the entire directory.
      file_unmanaged_delete_recursive($this->getFilesPath());
    }
    else {
      // Get all Fast Autocomplete json files.
      $json_files = file_scan_directory($this->getFilesPath(), '/.*\.json$/');

      // Loop through all the files and delete those that have expired.
      foreach ($json_files as $json_file) {
        if (filectime($json_file->uri) < $expiry_time) {
          file_unmanaged_delete($json_file->uri);
        }
      }
    }
  }

  /**
   * Gets the Fast Autocomplete configuration JSON files filepath.
   *
   * @returns string
   *   The JSON files filepath.
   */
  protected function getFilesPath() {
    return PublicStream::basePath() . '/fac-json/' . $this->id();
  }

}
