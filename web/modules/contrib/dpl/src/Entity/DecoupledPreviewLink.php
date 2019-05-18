<?php

namespace Drupal\dpl\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\dpl\PreviewLinkInstance;

/**
 * Defines an decoupled preview link  configuration entity.
 *
 * @ConfigEntityType(
 *   id = "decoupled_preview_link",
 *   label = @Translation("Decoupled preview link"),
 *   label_collection = @Translation("Preview links"),
 *   label_singular = @Translation("Preview link"),
 *   label_plural = @Translation("Preview links"),
 *   label_count = @PluralTranslation(
 *     singular = "@count preview link",
 *     plural = "@count preview links",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\dpl\Form\PreviewLinkAddForm",
 *       "edit" = "Drupal\dpl\Form\PreviewLinkEditForm",
 *       "delete" = "Drupal\dpl\Form\PreviewLinkDeleteForm",
 *     },
 *     "list_builder" = "\Drupal\dpl\Entity\DecoupledPreviewLinkListBuilder",
 *     "route_provider" = {
 *       "html" = "\Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer site configuration",
 *   config_prefix = "dpl",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/services/decoupled_preview_link/{decoupled_preview_link}",
 *     "collection" = "/admin/config/services/decoupled_preview_link",
 *     "add-form" = "/admin/config/services/decoupled_preview_link/add",
 *     "edit-form" = "/admin/config/services/decoupled_preview_link/{decoupled_preview_link}/edit",
 *     "delete-form" = "/admin/config/services/decoupled_preview_link/{decoupled_preview_link}/delete"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "tab_label",
 *     "open_external_label",
 *     "preview_url",
 *     "default_size",
 *   }
 * )
 */
class DecoupledPreviewLink extends ConfigEntityBase {

  /**
   * The link machine name.
   *
   * @var string
   */
  protected $id;

  /**
   * The link label.
   *
   * @var string
   */
  protected $label;

  /**
   * The tab label.
   *
   * @var string
   */
  protected $tab_label;

  /**
   * The preview URL before token replacement.
   *
   * @var string
   */
  protected $preview_url;

  /**
   * The preview URL before token replacement.
   *
   * @var string
   */
  protected $open_external_label;

  /**
   * @var array
   */
  protected $sizes;

  /**
   * @var string
   */
  protected $default_size = 's';

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * Returns the preview link instance associated with this config.
   *
   * @return \Drupal\dpl\PreviewLinkInstance
   */
  public function toPreviewLinkInstance() {
    return new PreviewLinkInstance(
      $this->id(),
      $this->getTabLabel(),
      $this->getOpenExternalLabel(),
      $this->getPreviewUrl(),
      $this->getDefaultSize()
    );
  }

  /**
   * @return string
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * @return string
   */
  public function getTabLabel() {
    return $this->tab_label;
  }

  /**
   * @return string
   */
  public function getPreviewUrl() {
    return $this->preview_url;
  }

  /**
   * @return string
   */
  public function getOpenExternalLabel() {
    return $this->open_external_label;
  }

  /**
   * @return string
   */
  public function getDefaultSize() {
    return $this->default_size;
  }

}
