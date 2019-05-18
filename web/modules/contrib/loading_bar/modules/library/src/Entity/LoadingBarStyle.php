<?php

namespace Drupal\loading_bar_library\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines an loading bar style configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "loading_bar_style",
 *   label = @Translation("loading bar style"),
 *   label_singular = @Translation("loading bar style"),
 *   label_plural = @Translation("loading bar styles"),
 *   label_count = @PluralTranslation(
 *     singular = "@count loading bar style",
 *     plural = "@count loading bar styles"
 *   ),
 *   admin_permission = "administer loading bar styles",
 *   handlers = {
 *     "list_builder" = "Drupal\loading_bar_library\LoadingBarStyleListBuilder",
 *     "form" = {
 *       "default" = "Drupal\loading_bar_library\Form\LoadingBarStyleForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider"
 *     },
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/loading-bar-library/add",
 *     "edit-form" = "/admin/structure/loading-bar-library/{loading_bar_style}/edit",
 *     "delete-form" = "/admin/structure/loading-bar-library/{loading_bar_style}/delete",
 *     "collection" = "/admin/structure/loading-bar-library"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "configuration"
 *   }
 * )
 */
class LoadingBarStyle extends ConfigEntityBase implements LoadingBarStyleInterface {

  /**
   * The ID of the loading bar style.
   *
   * @var string
   */
  protected $id;

  /**
   * The label of the loading bar style.
   *
   * @var string
   */
  protected $label;

  /**
   * The configuration of loading bar style.
   *
   * @var array
   */
  protected $configuration = [];

  /**
   * The description of the loading bar style.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration = []) {
    $this->configuration = $configuration;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Invalidate the cache ID of the prebuilt styles.
    \Drupal::cache()->invalidate(LOADING_BAR_PRESET_CID);
  }

}
