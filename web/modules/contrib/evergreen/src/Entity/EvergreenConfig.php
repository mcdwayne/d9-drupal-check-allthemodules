<?php

namespace Drupal\evergreen\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\evergreen\Entity\EvergreenConfigInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the EvergreenConfig entity.
 *
 * EvergreenConfig config entities define a configuration for a specific
 * entity/bundle. Per entity settings are then saved as EvergreenContent
 * entities.
 *
 * @ConfigEntityType(
 *   id = "evergreen_config",
 *   label = @Translation("Evergreen configuration"),
 *   fieldable = FALSE,
 *   handlers = {
 *     "list_builder" = "Drupal\evergreen\EvergreenConfigListBuilder",
 *     "form" = {
 *       "add" = "Drupal\evergreen\Form\EvergreenConfigForm",
 *       "edit" = "Drupal\evergreen\Form\EvergreenConfigForm",
 *       "delete" = "Drupal\evergreen\Form\EvergreenConfigDeleteForm",
 *     }
 *   },
 *   config_prefix = "evergreen_config",
 *   config_export = {
 *     "id",
 *     "evergreen_entity_type",
 *     "evergreen_bundle",
 *     "evergreen_expiry",
 *     "evergreen_expiry_provider",
 *     "evergreen_default_status",
 *   },
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "evergreen_entity_type" = "evergreen_entity_type",
 *     "evergreen_bundle" = "evergreen_bundle",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/content/evergreen/{evergreen_config}",
 *     "delete-form" = "/admin/config/content/evergreen/{evergreen_config}/delete",
 *   }
 * )
 */
class EvergreenConfig extends ConfigEntityBase implements EvergreenConfigInterface {

  const ENTITY_TYPE = 'evergreen_entity_type';
  const BUNDLE = 'evergreen_bundle';
  const STATUS = 'evergreen_default_status';
  const EXPIRY = 'evergreen_expiry';
  const EXPIRY_PROVIDER = 'evergreen_expiry_provider';

  /**
   * The ID of the block.
   *
   * @var string
   */
  protected $id;

  /**
   * The entity type for the associated content.
   *
   * @var string
   */
  protected $evergreen_entity_type;

  /**
   * The bundle for the associated content.
   *
   * @var string
   */
  protected $evergreen_bundle;

  /**
   * The associated expiry time.
   *
   * The expiry time is the time from last review that the content will "expire"
   * if it is not evergreen.
   *
   * @var int
   */
  protected $evergreen_expiry;

  /**
   * The name of the expiry options provider.
   *
   * @var string
   */
  protected $evergreen_expiry_provider;

  /**
   * Default evergreen status for this content.
   *
   * @var int
   */
  protected $evergreen_default_status;

  /**
   * Check the bundle value.
   */
  public function checkBundle() {
    $exploded = explode('.', $this->getEvergreenBundle());
    $this->evergreen_bundle = array_pop($exploded);
    return $this;
  }

  /**
   * Check the expiry value.
   */
  public function checkExpiry() {
    $this->evergreen_expiry = evergreen_parse_expiry($this->getEvergreenExpiry());
    return $this;
  }

  /**
   * Generate the machine name from the entity_type and bundle.
   */
  public function generateId() {
    if (!$this->id()) {
      $this->id = $this->getEvergreenEntityType() . '.' . $this->getEvergreenBundle();
    }
    return $this;
  }

  /**
   * Get the bundle
   */
  public function getEvergreenBundle() {
    return $this->evergreen_bundle;
  }

  /**
   * Get the entity type.
   */
  public function getEvergreenEntityType() {
    return $this->evergreen_entity_type;
  }

  /**
   * Get the expiry time for this configuration.
   */
  public function getEvergreenExpiry() {
    if (!$this->evergreen_expiry) {
      return evergreen_expiry_default();
    }
    return $this->evergreen_expiry;
  }

  /**
   * Get status for this configuration.
   */
  public function getEvergreenStatus() {
    return $this->evergreen_default_status;
  }

  /**
   * Get the expiry options form.
   */
  public function getExpiryOptionsForm() {
    $provider = $this->get(self::EXPIRY_OPTION_PROVIDER);
    if (!$provider) {
      throw new \Exception('No expiry options provider specified for this configuration');
    }
    $options_provider = new $provider();
    return $options_provider->getFormElement($this->getEvergreenExpiry());
  }

}
