<?php
/**
 * @file
 * Contains \Drupal\widget_block\Entity\WidgetBlockConfig.
 */

namespace Drupal\widget_block\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Render\Markup;

/**
 * Defines the widget block configuration entity.
 *
 * @ConfigEntityType(
 *   id = "widget_block_config",
 *   label = @Translation("Widget Block"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\widget_block\Form\WidgetBlockConfigEditForm",
 *       "add" = "Drupal\widget_block\Form\WidgetBlockConfigEditForm",
 *       "edit" = "Drupal\widget_block\Form\WidgetBlockConfigEditForm",
 *       "delete" = "Drupal\widget_block\Form\WidgetBlockConfigDeleteForm",
 *       "refresh" = "Drupal\widget_block\Form\WidgetBlockConfigRefreshForm",
 *       "invalidate" = "Drupal\widget_block\Form\WidgetBlockConfigInvalidateForm"
 *     },
 *     "list_builder" = "Drupal\widget_block\WidgetBlockListBuilder"
 *   },
 *   admin_permission = "administer widget_block",
 *   config_prefix = "config",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/block/widget-block",
 *     "edit-form" = "/admin/structure/block/widget-block/edit/{widget_block_config}",
 *     "delete-form" = "/admin/structure/block/widget-block/delete/{widget_block_config}",
 *     "refresh-form" = "/admin/structure/block/widget-block/refresh/{widget_block_config}",
 *     "invalidate-form" = "/admin/structure/block/widget-block/invalidate/{widget_block_config}"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "protocol",
 *     "hostname",
 *     "mode",
 *   }
 * )
 */
class WidgetBlockConfig extends ConfigEntityBase implements WidgetBlockConfigInterface {

  /**
   * The widget block identifier.
   *
   * @var string
   */
  protected $id;

  /**
   * The widget block label.
   *
   * @var string
   */
  protected $label;

  /**
   * The protocol which should be used during server to server communication.
   *
   * @var string
   */
  protected $protocol = WidgetBlockConfigInterface::PROTOCOL_HTTPS;

  /**
   * The hostname where the widgets are hosted.
   *
   * @var string
   */
  protected $hostname = 'widgets.vlaanderen.be';

  /**
   * The mode which should be used for including widgets.
   *
   * @var string
   */
  protected $mode = WidgetBlockConfigInterface::MODE_EMBED;

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    // Perform default post-save operation.
    parent::postSave($storage, $update);
    // Invalidate the block cache to update widget block-based derivatives.
    \Drupal::service('plugin.manager.block')->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function getProtocol() {
    return $this->protocol;
  }

  /**
   * {@inheritod}
   */
  public function setProtocol($protocol) {
    $this->protocol = $protocol;
  }

  /**
   * {@inheritdoc}
   */
  public function getHostname() {
    return $this->hostname;
  }

  /**
   * {@inheritdoc}
   */
  public function setHostname($hostname) {
    $this->hostname = $hostname;
  }

  /**
   * {@inheritdoc}
   */
  public function getIncludeMode() {
    return $this->mode;
  }

  /**
   * {@inheritdoc}
   */
  public function setIncludeMode($mode) {
    $this->mode = $mode;
  }

  /**
   * {@inheritdoc}
   */
  public function getMarkup() {
    // Get the widget block markup for this configuration.
    return \Drupal::service('widget_block.backend')->getMarkup($this, \Drupal::languageManager()->getCurrentLanguage());
  }

}
