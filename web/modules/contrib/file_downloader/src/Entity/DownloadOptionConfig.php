<?php

namespace Drupal\file_downloader\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileInterface;
use Drupal\file_downloader\DownloadOptionPluginCollection;

/**
 * Defines the Download option config entity.
 *
 * @ConfigEntityType(
 *   id = "download_option_config",
 *   label = @Translation("Download Option"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" =
 *   "Drupal\file_downloader\DownloadOptionConfigListBuilder",
 *     "form" = {
 *       "add" = "Drupal\file_downloader\Form\DownloadOptionConfigForm",
 *       "edit" = "Drupal\file_downloader\Form\DownloadOptionConfigForm",
 *       "delete" =
 *   "Drupal\file_downloader\Form\DownloadOptionConfigDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" =
 *   "Drupal\file_downloader\DownloadOptionConfigHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "download_option_config",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" =
 *   "/admin/config/media/download_options/{download_option_config}",
 *     "add-form" = "/admin/config/media/download_options/add",
 *     "edit-form" =
 *   "/admin/config/media/download_options/{download_option_config}/edit",
 *     "delete-form" =
 *   "/admin/config/media/download_options/{download_option_config}/delete",
 *     "collection" = "/admin/config/media/download_options"
 *   }
 * )
 */
class DownloadOptionConfig extends ConfigEntityBase implements DownloadOptionConfigInterface {

  /**
   * The Download option config ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Download option config label.
   *
   * @var string
   */
  protected $label;

  /**
   * The id of the selected plugin.
   *
   * @var string
   */
  protected $plugin_id;

  /**
   * String containing $extensions
   *
   * @var string
   */
  protected $extensions;

  /**
   * The plugin instance settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * Download option plugin colletcion.
   *
   * @var \Drupal\file_downloader\DownloadOptionPluginCollection
   */
  private $pluginCollection;

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->plugin_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensions() {
    return $this->extensions;
  }

  /**
   * {@inheritdoc}
   */
  public function accessDownload(AccountInterface $account, FileInterface $file = NULL) {
    if (!$account->hasPermission("use " . $this->id() . " download option link")) {
      return AccessResult::forbidden('Download option link is not accessible for the user.');
    }

    if (!isset($file)) {
      return AccessResult::neutral();
    }

    if (!$this->validFileExtensions($file)) {
      return AccessResult::forbidden('File is not a valid extension to be used with this download option.');
    }

    return $this->getPlugin()->access($account, $file);
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    if (isset($this->plugin_id)) {
      return $this->getPluginCollection($this)->get($this->plugin_id);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollection(DownloadOptionConfigInterface $downloadOptionConfig) {
    if (!$this->pluginCollection) {
      $this->pluginCollection = new DownloadOptionPluginCollection(\Drupal::service('plugin.manager.download_option'), $this->plugin_id, $downloadOptionConfig->get('settings'), $this->id());
    }
    return $this->pluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionList() {
    $extension_config = $this->getExtensions();
    $extensions = str_replace(',', ' ', $extension_config);
    return array_filter(explode(' ', $extensions));
  }

  /**
   * {@inheritdoc}
   */
  public function validFileExtensions(FileInterface $file) {
    $extensions = $this->getExtensionList();
    return (empty($extensions) || in_array(pathinfo($file->getFilename(), PATHINFO_EXTENSION), $extensions));
  }

}
