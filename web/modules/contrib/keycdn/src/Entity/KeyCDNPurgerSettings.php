<?php

namespace Drupal\keycdn\Entity;

use Drupal\purge\Plugin\Purge\Purger\PurgerSettingsBase;
use Drupal\purge\Plugin\Purge\Purger\PurgerSettingsInterface;

/**
 * Defines the KeyCDN purger settings entity.
 *
 * @ConfigEntityType(
 *   id = "keycdnpurgersettings",
 *   label = @Translation("KeyCDN purger settings"),
 *   config_prefix = "settings",
 *   static_cache = TRUE,
 *   entity_keys = {"id" = "id"},
 * )
 */
class KeyCDNPurgerSettings extends PurgerSettingsBase implements PurgerSettingsInterface {

  /**
   * The readable name of this purger.
   *
   * @var string
   */
  public $name = '';

  /**
   * The Zone
   *
   * @var string
   */
  public $zone;

  /**
   * The API key
   *
   * @var string
   */
  public $api_key;
}
