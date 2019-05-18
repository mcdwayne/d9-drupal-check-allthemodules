<?php

/**
 * @file
 * Contains \Drupal\bideo\Entity\Bideo.
 */

namespace Drupal\bideo\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\bideo\BideoInterface;

/**
 * Defines an batch video configuration entity.
 *
 * @ConfigEntityType(
 *   id = "bideo",
 *   label = @Translation("Batch video"),
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage"
 *   },
 *   config_prefix = "bideo",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   }
 * )
 */
class Bideo extends ConfigEntityBase implements BideoInterface {

  /**
   * The id of the batch video.
   *
   * @var string
   */
  public $id;

  /**
   * The batch video label.
   *
   * @var string
   */
  public $label;

  /**
   * The plugin settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $plugin_id;

  /**
   * {@inheritdoc}
   */
  public function render() {
    $plugin = $this->plugin_id;
    /** @var \Drupal\bideo\BideoPluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.bideo');
    /** @var \Drupal\bideo\BideoPluginInterface $plugin */
    $plugin = $plugin_manager->createInstance($plugin, $this->settings);
    return $plugin->render();
  }
}
